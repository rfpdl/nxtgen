<?php

declare(strict_types=1);

namespace App\Operations\Auth;

use App\Data\Models\FamilyClaim;
use App\Data\Models\User;
use App\Domains\Auth\Jobs\GetMagicLinkByTokenJob;
use App\Domains\Auth\Jobs\RedeemMagicLinkJob;
use App\Domains\Family\Jobs\GetGuardianForUserJob;
use App\Domains\Family\Jobs\UpdateFamilyClaimStatusJob;
use App\Domains\Family\Jobs\UpdateGuardianUserJob;
use App\Domains\User\Jobs\CreateUserJob;
use App\Domains\User\Jobs\GetUserByEmailJob;
use App\Domains\User\Jobs\GetUserForUpdateJob;
use App\Domains\User\Jobs\UpdateUserEmailVerifiedJob;
use App\Enums\FamilyClaimStatusEnum;
use App\Enums\MagicLinkPurposeEnum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Lucid\Units\Operation;

/**
 * Accept a family claim from its emailed link (plan §4.2). Takes the SAME lock as registration for that
 * church + email, rechecks under it, consumes the link atomically, and links the guardian to the login
 * (creating the login if the address has none yet). If the login gained a family in this church in the
 * meantime, the claim is revoked instead — one family per user per church.
 *
 * Returns the user to sign in, or null when the link or claim is no longer valid.
 *
 * @extends Operation<User|null>
 */
final class AcceptFamilyClaimOperation extends Operation
{
    public function __construct(private readonly string $token) {}

    public function handle(): ?User
    {
        $peek = $this->run(new GetMagicLinkByTokenJob(token: $this->token, purpose: MagicLinkPurposeEnum::ClaimFamily));
        $claim = $peek?->familyClaim;

        if (! $claim instanceof FamilyClaim) {
            return null;
        }

        try {
            /** @var User|null */
            return Cache::lock("register:{$claim->organization_id}:{$claim->email_normalised}", 15)
                ->block(10, fn (): ?User => DB::transaction(fn (): ?User => $this->accept($claim)));
        } catch (ClaimBindingFailed) {
            return null; // the whole transaction (link consumption included) rolled back
        }
    }

    private function accept(FamilyClaim $claim): ?User
    {
        $link = $this->run(new RedeemMagicLinkJob(token: $this->token, purpose: MagicLinkPurposeEnum::ClaimFamily));
        if ($link === null) {
            return null;
        }

        $found = $this->run(new GetUserByEmailJob(email: $claim->email_normalised));

        // Lock the login and re-check its email UNDER the lock: an email correction elsewhere must not let a
        // claim attach to an account that no longer owns this address (Codex diff review, high).
        $user = $found !== null
            ? $this->run(new GetUserForUpdateJob(userId: $found->id))
            : $this->run(new CreateUserJob(name: $claim->guardian->first_name.' '.$claim->guardian->last_name, email: $claim->email_normalised));

        if ($user->email !== $claim->email_normalised) {
            throw new ClaimBindingFailed;
        }

        if ($this->run(new GetGuardianForUserJob(user: $user, organization: $claim->family->organization)) !== null) {
            $this->run(new UpdateFamilyClaimStatusJob(claim: $claim, status: FamilyClaimStatusEnum::Revoked));

            return null;
        }

        $this->run(new UpdateGuardianUserJob(guardian: $claim->guardian, user: $user));
        $this->run(new UpdateFamilyClaimStatusJob(claim: $claim, status: FamilyClaimStatusEnum::Accepted));

        if (! $this->run(new UpdateUserEmailVerifiedJob(user: $user, boundEmail: $claim->email_normalised, boundVersion: $user->credential_version))) {
            throw new ClaimBindingFailed;
        }

        return $user;
    }
}
