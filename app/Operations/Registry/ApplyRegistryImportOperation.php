<?php

declare(strict_types=1);

namespace App\Operations\Registry;

use App\Data\Models\Church;
use App\Data\Models\Family;
use App\Data\Models\Group;
use App\Data\Models\Satellite;
use App\Domains\Family\DataObjects\ChildDetailsData;
use App\Domains\Family\DataObjects\FamilyRegistrationData;
use App\Domains\Family\DataObjects\GuardianDetailsData;
use App\Domains\Family\Jobs\GetFamilyByIdJob;
use App\Domains\Family\Jobs\GetMatchingChildJob;
use App\Domains\Registry\DataObjects\ApprovedChildRowData;
use App\Domains\Registry\DataObjects\ImportResultData;
use App\Domains\Registry\Jobs\CreateImportMappingJob;
use App\Domains\Registry\Jobs\GetImportedSourceKeysJob;
use App\Enums\ChildStatusEnum;
use App\Enums\GenderEnum;
use App\Enums\RegistrationOutcomeEnum;
use App\Enums\RegistrationSourceEnum;
use App\Enums\RelationshipEnum;
use App\Operations\Family\AddChildToFamilyOperation;
use App\Operations\Registration\RegisterFamilyOperation;
use Illuminate\Support\Facades\DB;
use Lucid\Units\Operation;

/**
 * Load the REVIEWED families (plan §7) through the same registration path as everyone else, with no emails.
 * Idempotent: a child whose source key is already mapped is skipped, so the file can be applied twice.
 *
 * @extends Operation<ImportResultData>
 */
final class ApplyRegistryImportOperation extends Operation
{
    /** @var array<string, Group|null> reviewed group names resolved once */
    private array $groupCache = [];

    /** @param array<string, list<ApprovedChildRowData>> $groups */
    public function __construct(
        private readonly Church $church,
        private readonly array $groups,
    ) {}

    public function handle(): ImportResultData
    {
        $mapped = $this->run(new GetImportedSourceKeysJob(church: $this->church));
        $satellites = Satellite::query()->where('church_id', $this->church->id)->get()->keyBy(fn (Satellite $s): string => mb_strtolower($s->name));
        $families = $children = $skipped = 0;
        $awaitingClaim = [];
        $problems = [];

        foreach ($this->groups as $familyKey => $rows) {
            $pending = array_values(array_filter($rows, fn (ApprovedChildRowData $r): bool => ! isset($mapped[$r->get('Source key')])));
            $skipped += count($rows) - count($pending);

            // A corrected sheet row gets a new source key; never insert a child the church already has.
            // The reviewer can mark a row "Distinct child = yes" for a genuinely different child who shares a name
            // and birthday with one already loaded (Codex diff review: name + birthday is evidence, not identity).
            $pending = array_values(array_filter($pending, function (ApprovedChildRowData $r) use (&$problems): bool {
                if ($r->yes('Distinct child')) {
                    return true;
                }
                $existing = $this->run(new GetMatchingChildJob(
                    churchId: $this->church->id,
                    firstName: $r->get('Child first name'),
                    lastName: $r->get('Child last name'),
                    dateOfBirth: $r->get('Birthday'),
                ));
                if ($existing !== null) {
                    $problems[] = "Sheet row {$r->get('Sheet row')}: {$r->get('Child first name')} {$r->get('Child last name')} ({$r->get('Birthday')}) is already in the app — not loaded again (set Distinct child = yes if this is a different child)";

                    return false;
                }

                return true;
            }));

            if ($pending === []) {
                continue;
            }

            // A reviewed group must exist in this church; an unknown name is a problem, never silently replaced.
            $badGroup = array_filter($pending, fn (ApprovedChildRowData $r): bool => $r->get('Group') !== '' && $this->reviewedGroup($r) === null);
            if ($badGroup !== []) {
                $problems[] = "Family {$familyKey}: unknown group \"".array_values($badGroup)[0]->get('Group').'" — not loaded';

                continue;
            }

            $primary = $rows[0];

            // A group must point at ONE family. Rows already imported into different families, or a parent email
            // that differs from the family they were imported into, need a person to reconcile — never guess.
            $mappedFamilies = array_values(array_unique(array_filter(array_map(fn (ApprovedChildRowData $r): ?int => $mapped[$r->get('Source key')] ?? null, $rows))));
            if (count($mappedFamilies) > 1) {
                $problems[] = "Family {$familyKey}: rows were already imported into different families — not loaded, reconcile by hand";

                continue;
            }
            if (count($mappedFamilies) === 1 && ! $this->sameGuardian($mappedFamilies[0], $primary->get('Parent email'))) {
                $problems[] = "Family {$familyKey}: parent email differs from the family it was imported into — not loaded";

                continue;
            }

            $satellite = $satellites->get(mb_strtolower($primary->get('Home satellite')));
            if (! $satellite instanceof Satellite) {
                $problems[] = "Family {$familyKey}: unknown satellite \"{$primary->get('Home satellite')}\" — not loaded";

                continue;
            }

            DB::transaction(function () use ($rows, $pending, $primary, $satellite, $mapped, $familyKey, &$families, &$children, &$awaitingClaim): void {
                $family = $this->existingFamily($rows, $mapped) ?? $this->register($primary, $satellite, $families, $awaitingClaim);

                foreach ($pending as $row) {
                    $child = $this->run(new AddChildToFamilyOperation(
                        family: $family,
                        details: $this->child($row),
                        source: RegistrationSourceEnum::Import,
                        group: $this->reviewedGroup($row),
                    ));
                    $this->run(new CreateImportMappingJob(child: $child, sourceKey: $row->get('Source key'), familyKey: (string) $familyKey));
                    $children++;
                }
            });
        }

        return new ImportResultData($families, $children, $skipped, $problems, $awaitingClaim);
    }

    /**
     * @param  list<ApprovedChildRowData>  $rows
     * @param  array<string, int>  $mapped
     */
    private function existingFamily(array $rows, array $mapped): ?Family
    {
        foreach ($rows as $row) {
            if (isset($mapped[$row->get('Source key')])) {
                return $this->run(new GetFamilyByIdJob(churchId: $this->church->id, id: $mapped[$row->get('Source key')]));
            }
        }

        return null;
    }

    /** @param list<string> $awaitingClaim */
    private function register(ApprovedChildRowData $p, Satellite $satellite, int &$families, array &$awaitingClaim): Family
    {
        $result = $this->run(new RegisterFamilyOperation(new FamilyRegistrationData(
            church: $this->church,
            homeSatellite: $satellite,
            primary: new GuardianDetailsData(
                firstName: $p->get('Parent first name'),
                lastName: $p->get('Parent last name'),
                relationship: RelationshipEnum::tryFrom($p->get('Relationship')) ?? RelationshipEnum::Other,
                phone: $p->orNull('Phone'),
                email: $p->get('Parent email'),
                ministries: $p->orNull('Ministry') !== null ? [$p->get('Ministry')] : [],
            ),
            children: [],
            areaText: $p->orNull('Area'),
            privacyConsented: $p->yes('Privacy consented'),
            source: RegistrationSourceEnum::Import,
            fbAccount: $p->orNull('FB account'),
            wantsFbGroup: $p->yes('FB group'),
            inDgroup: $p->yes('In DGroup'),
            dgroupAreaText: $p->orNull('DGroup area'),
            dgroupLeaderName: $p->orNull('DGroup leader'),
            dgroupLeaderPhone: $p->orNull('DGroup leader phone'),
            wantsDgroup: $p->yes('Wants DGroup'),
            lifestage: $p->orNull('Lifestage'),
            notify: false,
        )));

        // The email is already someone's login (often staff added on People before the import): the family waits
        // for them to confirm, and the import sends nothing — so the operator must be told (review 3, B3).
        if ($result->outcome === RegistrationOutcomeEnum::ClaimSent || $result->outcome === RegistrationOutcomeEnum::ClaimResent) {
            $awaitingClaim[] = mb_strtolower(trim($p->get('Parent email')));
        }

        // Count only families this import actually created; an email already registered here reuses its family.
        if ($result->outcome !== RegistrationOutcomeEnum::AlreadyRegistered && $result->outcome !== RegistrationOutcomeEnum::ClaimResent) {
            $families++;
        }

        return $result->family ?? throw new \RuntimeException('Registration returned no family.');
    }

    private function child(ApprovedChildRowData $row): ChildDetailsData
    {
        return new ChildDetailsData(
            firstName: $row->get('Child first name'),
            middleName: null,
            lastName: $row->get('Child last name'),
            nickname: $row->get('Nickname'),
            gender: GenderEnum::from($row->get('Gender')),
            dateOfBirth: $row->get('Birthday'),
            allergies: $row->orNull('Allergies'),
            dislikes: $row->orNull('Dislikes'),
            fears: $row->orNull('Fears'),
            parentNotes: $row->orNull('Notes'),
            status: ChildStatusEnum::tryFrom($row->get('Status')) ?? ChildStatusEnum::Active,
        );
    }

    private function sameGuardian(int $familyId, string $email): bool
    {
        $family = $this->run(new GetFamilyByIdJob(churchId: $this->church->id, id: $familyId));

        return mb_strtolower(trim($family->contactEmail())) === mb_strtolower(trim($email));
    }

    private function reviewedGroup(ApprovedChildRowData $row): ?Group
    {
        $name = $row->get('Group');
        if ($name === '') {
            return null;
        }

        return $this->groupCache[mb_strtolower($name)] ??= Group::query()
            ->where('church_id', $this->church->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();
    }
}
