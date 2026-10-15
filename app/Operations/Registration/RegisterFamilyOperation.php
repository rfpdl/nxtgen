<?php

declare(strict_types=1);

namespace App\Operations\Registration;

use App\Data\Models\Family;
use App\Data\Models\User;
use App\Domains\Auth\DataObjects\IssuedMagicLinkData;
use App\Domains\Auth\Jobs\CreateMagicLinkJob;
use App\Domains\Auth\Jobs\SendMagicLinkJob;
use App\Domains\Family\DataObjects\FamilyRegistrationData;
use App\Domains\Family\Jobs\CreateChildJob;
use App\Domains\Family\Jobs\CreateDuplicateCandidatesJob;
use App\Domains\Family\Jobs\CreateFamilyClaimJob;
use App\Domains\Family\Jobs\CreateFamilyJob;
use App\Domains\Family\Jobs\CreateGuardianJob;
use App\Domains\Family\Jobs\GetGuardianForUserJob;
use App\Domains\Family\Jobs\GetPendingFamilyClaimJob;
use App\Domains\Family\Jobs\UpdateFamilyPrimaryGuardianJob;
use App\Domains\Group\Jobs\GetSuggestedGroupJob;
use App\Domains\User\Jobs\CreateUserJob;
use App\Domains\User\Jobs\GetUserByEmailJob;
use App\Enums\MagicLinkPurposeEnum;
use App\Enums\RegistrationOutcomeEnum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Lucid\Units\Operation;

/**
 * THE registration path — parent, staff and import all come through here (plan §4.2).
 *
 *   email's user already a guardian in this church  → AlreadyRegistered, login link, nothing created
 *   pending claim already exists for this email     → ClaimResent, nothing created
 *   email belongs to a user with no family here     → family + UNLINKED primary guardian + claim → ClaimSent
 *   unknown email                                    → user (unverified) + family + LINKED primary → Registered
 *
 * Serialised per (church, email) by a lock, and backed by the database's unique keys, so two submissions
 * of the same form cannot create two families. Emails go out only after the transaction commits — and not at
 * all for an import (`notify: false`), which creates no links either.
 *
 * @extends Operation<RegistrationResultData>
 */
final class RegisterFamilyOperation extends Operation
{
    public function __construct(private readonly FamilyRegistrationData $registration) {}

    public function handle(): RegistrationResultData
    {
        $r = $this->registration;
        $email = $r->normalisedEmail();

        /** @var array{0: RegistrationResultData, 1: IssuedMagicLinkData|null} $done */
        $done = Cache::lock("register:{$r->organization->id}:{$email}", 15)
            ->block(10, fn (): array => DB::transaction(fn (): array => $this->register($email)));

        [$result, $issued] = $done;

        if ($issued !== null) {
            $this->run(new SendMagicLinkJob(
                issued: $issued,
                churchName: $r->organization->name,
                alreadyRegistered: $result->outcome === RegistrationOutcomeEnum::AlreadyRegistered,
            ));
        }

        return $result;
    }

    /** @return array{0: RegistrationResultData, 1: IssuedMagicLinkData|null} */
    private function register(string $email): array
    {
        $r = $this->registration;
        $user = $this->run(new GetUserByEmailJob(email: $email));

        if ($user instanceof User) {
            $guardian = $this->run(new GetGuardianForUserJob(user: $user, organization: $r->organization));
            if ($guardian !== null) {
                $issued = $this->link(new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::Login, email: $email, user: $user));

                return [new RegistrationResultData(RegistrationOutcomeEnum::AlreadyRegistered, $guardian->family), $issued];
            }
        }

        $pending = $this->run(new GetPendingFamilyClaimJob(organization: $r->organization, email: $email));
        if ($pending !== null) {
            $issued = $this->link(new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::ClaimFamily, email: $email, claim: $pending));

            return [new RegistrationResultData(RegistrationOutcomeEnum::ClaimResent, $pending->family), $issued];
        }

        $family = $this->run(new CreateFamilyJob(registration: $r));

        if ($user instanceof User) {
            $guardian = $this->run(new CreateGuardianJob(family: $family, details: $r->primary, user: null));
            $this->run(new UpdateFamilyPrimaryGuardianJob(family: $family, guardian: $guardian));
            $claim = $this->run(new CreateFamilyClaimJob(family: $family, guardian: $guardian, email: $email));
            $issued = $this->link(new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::ClaimFamily, email: $email, claim: $claim));
            $outcome = RegistrationOutcomeEnum::ClaimSent;
        } else {
            $user = $this->run(new CreateUserJob(name: $r->primary->firstName.' '.$r->primary->lastName, email: $email));
            $guardian = $this->run(new CreateGuardianJob(family: $family, details: $r->primary, user: $user));
            $this->run(new UpdateFamilyPrimaryGuardianJob(family: $family, guardian: $guardian));
            $issued = $this->link(new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::VerifyEmail, email: $email, user: $user));
            $outcome = RegistrationOutcomeEnum::Registered;
        }

        $this->addChildren($family);
        $this->run(new CreateDuplicateCandidatesJob(family: $family));

        return [new RegistrationResultData($outcome, $family), $issued];
    }

    private function addChildren(Family $family): void
    {
        foreach ($this->registration->children as $child) {
            $group = $this->run(new GetSuggestedGroupJob(
                organization: $this->registration->organization,
                gender: $child->gender,
                dateOfBirth: $child->dateOfBirth,
            ));

            $this->run(new CreateChildJob(family: $family, details: $child, group: $group, source: $this->registration->source));
        }
    }

    /** An import never creates or sends links; everyone else does. */
    private function link(CreateMagicLinkJob $job): ?IssuedMagicLinkData
    {
        return $this->registration->notify ? $this->run($job) : null;
    }
}
