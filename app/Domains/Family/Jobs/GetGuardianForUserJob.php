<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Church;
use App\Data\Models\Guardian;
use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;

/**
 * The user's guardian row in this church, if any, with its family and church — at most one (database-enforced).
 * A parent's own page reads theirs with GetOwnGuardianJob (the address names no church).
 *
 * @extends BaseJob<Guardian|null>
 */
final class GetGuardianForUserJob extends BaseJob
{
    public function __construct(
        private readonly User $user,
        private readonly Church $church,
    ) {}

    public function handle(): ?Guardian
    {
        return Guardian::query()
            ->with('family.church')
            ->where('user_id', $this->user->id)
            ->where('church_id', $this->church->id)
            ->first();
    }
}
