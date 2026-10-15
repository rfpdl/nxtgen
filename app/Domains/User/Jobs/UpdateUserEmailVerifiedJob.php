<?php

declare(strict_types=1);

namespace App\Domains\User\Jobs;

use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;

/**
 * Mark the email confirmed — ONLY if it is still the address (and credential version) the link was sent to.
 * One conditional UPDATE, so a correction that lands after redemption can never be "confirmed" by a link sent
 * to the old address. Returns whether this login is confirmed for the bound address.
 *
 * @extends BaseJob<bool>
 */
final class UpdateUserEmailVerifiedJob extends BaseJob
{
    public function __construct(
        private readonly User $user,
        private readonly string $boundEmail,
        private readonly int $boundVersion,
    ) {}

    public function handle(): bool
    {
        User::query()
            ->whereKey($this->user->id)
            ->where('email', $this->boundEmail)
            ->where('credential_version', $this->boundVersion)
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);

        return User::query()
            ->whereKey($this->user->id)
            ->where('email', $this->boundEmail)
            ->where('credential_version', $this->boundVersion)
            ->whereNotNull('email_verified_at')
            ->exists();
    }
}
