<?php

declare(strict_types=1);

namespace App\Services\Console\Features\Registry;

use App\Data\Models\Church;
use App\Data\Models\Family;
use App\Data\Models\Satellite;
use App\Domains\Church\Jobs\GetSatellitesByNameJob;
use App\Domains\Family\DataObjects\ChildDetailsData;
use App\Domains\Family\DataObjects\FamilyRegistrationData;
use App\Domains\Family\DataObjects\GuardianDetailsData;
use App\Domains\Family\Jobs\GetFamilyByIdJob;
use App\Domains\Family\Jobs\GetMatchingChildJob;
use App\Domains\Registry\DataObjects\ApprovedChildRowData;
use App\Domains\Registry\DataObjects\ImportResultData;
use App\Domains\Registry\Jobs\CreateImportMappingJob;
use App\Domains\Registry\Jobs\GetApprovedRegistryRowsJob;
use App\Domains\Registry\Jobs\GetImportedSourceKeysJob;
use App\Enums\ChildStatusEnum;
use App\Enums\GenderEnum;
use App\Enums\RegistrationOutcomeEnum;
use App\Enums\RegistrationSourceEnum;
use App\Enums\RelationshipEnum;
use App\Foundation\Attributes\UserStory;
use App\Foundation\Support\EmailAddress;
use App\Foundation\Support\LevelChart;
use App\Operations\Family\AddChildToFamilyOperation;
use App\Operations\Registration\RegisterFamilyOperation;
use App\Services\Console\Features\BaseConsoleFeature;
use Illuminate\Support\Facades\DB;

/**
 * Step 2 of loading the existing Google Sheet (plan §7): load the REVIEWED families through the same registration
 * path as everyone else (RegisterFamilyOperation, then AddChildToFamilyOperation per child), with no emails.
 * Idempotent: a child whose source key is already mapped is skipped, so the file can be applied twice.
 *
 * A Feature, not an Operation, because it composes those two Operations — and an Operation never runs another
 * (aegis). Served from `nxtgen:registry-apply`; the approval file's own problems come first in the result.
 *
 * @extends BaseConsoleFeature<ImportResultData>
 */
#[UserStory(
    title: 'Load the reviewed registry',
    persona: 'church admin at the terminal',
    happyPaths: ['story#82' => 'The approved registry rows load as Families, without emailing anyone, and safely twice'],
    unhappyPaths: ['story#83' => 'Unapproved, conflicting or broken rows are not loaded, and the command fails'],
)]
final class ApplyRegistryImportFeature extends BaseConsoleFeature
{
    private int $familiesCreated = 0;

    private int $childrenCreated = 0;

    private int $childrenSkipped = 0;

    /** @var list<string> */
    private array $problems = [];

    /** @var list<string> */
    private array $awaitingClaim = [];

    public function __construct(
        private readonly Church $church,
        private readonly string $path,
    ) {}

    public function handle(): ImportResultData
    {
        $approval = $this->run(new GetApprovedRegistryRowsJob(path: $this->path));
        $this->problems = $approval->problems;
        $mapped = $this->run(new GetImportedSourceKeysJob(church: $this->church));
        $satellites = $this->run(new GetSatellitesByNameJob(church: $this->church));

        foreach ($approval->rows_by_family as $familyKey => $rows) {
            $this->applyFamily((string) $familyKey, $rows, $mapped, $satellites);
        }

        return new ImportResultData($this->familiesCreated, $this->childrenCreated, $this->childrenSkipped, $this->problems, $this->awaitingClaim);
    }

    /**
     * One family key: its rows not loaded yet, checked, then the family (found or registered) and its children in
     * one transaction. A family with a problem is reported and left out whole.
     *
     * @param  list<ApprovedChildRowData>  $rows
     * @param  array<string, int>  $mapped  source key => family id
     * @param  array<string, Satellite>  $satellites  by lower-cased name
     */
    private function applyFamily(string $familyKey, array $rows, array $mapped, array $satellites): void
    {
        $pending = $this->rowsToLoad($rows, $mapped);
        if ($pending === []) {
            return;
        }

        // A reviewed Level must be one of the chart's ("Level 2", "Level 2 Boys", "2"); an unknown one is a problem,
        // never silently replaced.
        $badLevel = array_filter($pending, fn (ApprovedChildRowData $r): bool => $r->get('Level') !== '' && $this->reviewedLevel($r) === null);
        if ($badLevel !== []) {
            $this->problems[] = "Family {$familyKey}: unknown Level \"".array_values($badLevel)[0]->get('Level').'" — not loaded';

            return;
        }

        $primary = $rows[0];

        // A family key must point at ONE family. Rows already imported into different families, or a parent email
        // that differs from the family they were imported into, need a person to reconcile — never guess.
        $mappedFamilies = array_values(array_unique(array_filter(array_map(fn (ApprovedChildRowData $r): ?int => $mapped[$r->get('Source key')] ?? null, $rows))));
        if (count($mappedFamilies) > 1) {
            $this->problems[] = "Family {$familyKey}: rows were already imported into different families — not loaded, reconcile by hand";

            return;
        }
        $existing = $mappedFamilies === [] ? null : $this->run(new GetFamilyByIdJob(church: $this->church, id: $mappedFamilies[0]));
        if ($existing !== null && EmailAddress::normalise($existing->contactEmail()) !== EmailAddress::normalise($primary->get('Parent email'))) {
            $this->problems[] = "Family {$familyKey}: parent email differs from the family it was imported into — not loaded";

            return;
        }

        $satellite = $satellites[mb_strtolower($primary->get('Home satellite'))] ?? null;
        if ($satellite === null) {
            $this->problems[] = "Family {$familyKey}: unknown satellite \"{$primary->get('Home satellite')}\" — not loaded";

            return;
        }

        DB::transaction(function () use ($existing, $pending, $primary, $satellite, $familyKey): void {
            $family = $existing ?? $this->register($primary, $satellite);

            foreach ($pending as $row) {
                $child = $this->run(new AddChildToFamilyOperation(
                    family: $family,
                    details: $this->child($row),
                    source: RegistrationSourceEnum::Import,
                    level: $this->reviewedLevel($row),
                ));
                $this->run(new CreateImportMappingJob(child: $child, sourceKey: $row->get('Source key'), familyKey: $familyKey));
                $this->childrenCreated++;
            }
        });
    }

    /**
     * The rows not loaded before: an already-mapped source key is skipped (counted), and a child the church already
     * has is never inserted again — a corrected sheet row gets a new source key. The reviewer can mark a row
     * "Distinct child = yes" for a genuinely different child who shares a name and birthday with one already loaded
     * (Codex diff review: name + birthday is evidence, not identity).
     *
     * @param  list<ApprovedChildRowData>  $rows
     * @param  array<string, int>  $mapped
     * @return list<ApprovedChildRowData>
     */
    private function rowsToLoad(array $rows, array $mapped): array
    {
        $pending = array_values(array_filter($rows, fn (ApprovedChildRowData $r): bool => ! isset($mapped[$r->get('Source key')])));
        $this->childrenSkipped += count($rows) - count($pending);

        return array_values(array_filter($pending, function (ApprovedChildRowData $r): bool {
            if ($r->yes('Distinct child')) {
                return true;
            }
            $existing = $this->run(new GetMatchingChildJob(
                church: $this->church,
                firstName: $r->get('Child first name'),
                lastName: $r->get('Child last name'),
                dateOfBirth: $r->get('Birthday'),
            ));
            if ($existing !== null) {
                $this->problems[] = "Sheet row {$r->get('Sheet row')}: {$r->get('Child first name')} {$r->get('Child last name')} ({$r->get('Birthday')}) is already in the app — not loaded again (set Distinct child = yes if this is a different child)";

                return false;
            }

            return true;
        }));
    }

    private function register(ApprovedChildRowData $p, Satellite $satellite): Family
    {
        $result = $this->run(new RegisterFamilyOperation($this->registration($p, $satellite)));

        // The email is already someone's login (often staff added on People before the import): the family waits
        // for them to confirm, and the import sends nothing — so the operator must be told (review 3, B3).
        if ($result->outcome === RegistrationOutcomeEnum::ClaimSent || $result->outcome === RegistrationOutcomeEnum::ClaimResent) {
            $this->awaitingClaim[] = EmailAddress::normalise($p->get('Parent email'));
        }

        // Count only families this import actually created; an email already registered here reuses its family.
        if ($result->outcome !== RegistrationOutcomeEnum::AlreadyRegistered && $result->outcome !== RegistrationOutcomeEnum::ClaimResent) {
            $this->familiesCreated++;
        }

        return $result->family ?? throw new \RuntimeException('Registration returned no family.');
    }

    /** A reviewed row's parent and family, as the one registration shape — no children (they follow), no emails. */
    private function registration(ApprovedChildRowData $p, Satellite $satellite): FamilyRegistrationData
    {
        return new FamilyRegistrationData(
            church: $this->church,
            home_satellite: $satellite,
            primary: new GuardianDetailsData(
                first_name: $p->get('Parent first name'),
                last_name: $p->get('Parent last name'),
                relationship: RelationshipEnum::tryFrom($p->get('Relationship')) ?? RelationshipEnum::Other,
                phone: $p->orNull('Phone'),
                email: $p->get('Parent email'),
                ministries: $p->orNull('Ministry') !== null ? [$p->get('Ministry')] : [],
            ),
            children: [],
            area_text: $p->orNull('Area'),
            privacy_consented: $p->yes('Privacy consented'),
            source: RegistrationSourceEnum::Import,
            fb_account: $p->orNull('FB account'),
            wants_fb_group: $p->yes('FB group'),
            in_dgroup: $p->yes('In DGroup'),
            dgroup_area_text: $p->orNull('DGroup area'),
            dgroup_leader_name: $p->orNull('DGroup leader'),
            dgroup_leader_phone: $p->orNull('DGroup leader phone'),
            wants_dgroup: $p->yes('Wants DGroup'),
            lifestage: $p->orNull('Lifestage'),
            notify: false,
        );
    }

    private function child(ApprovedChildRowData $row): ChildDetailsData
    {
        return new ChildDetailsData(
            first_name: $row->get('Child first name'),
            middle_name: null,
            last_name: $row->get('Child last name'),
            nickname: $row->get('Nickname'),
            gender: GenderEnum::from($row->get('Gender')),
            date_of_birth: $row->get('Birthday'),
            allergies: $row->orNull('Allergies'),
            dislikes: $row->orNull('Dislikes'),
            fears: $row->orNull('Fears'),
            guardian_notes: $row->orNull('Notes'),
            status: ChildStatusEnum::tryFrom($row->get('Status')) ?? ChildStatusEnum::default(),
        );
    }

    /** The Level the reviewer wrote in the "Level" column, or null when blank (the age then suggests one). */
    private function reviewedLevel(ApprovedChildRowData $row): ?int
    {
        $text = $row->get('Level');

        return $text === '' ? null : LevelChart::fromText($text);
    }
}
