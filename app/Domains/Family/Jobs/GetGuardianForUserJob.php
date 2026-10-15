<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Guardian;
use App\Data\Models\Organization;
use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;

/**
 * The user's guardian row in this church, if any. At most one (database-enforced).
 *
 * @extends BaseJob<Guardian|null>
 */
final class GetGuardianForUserJob extends BaseJob
{
    public function __construct(
        private readonly User $user,
        private readonly Organization $organization,
    ) {}

    public function handle(): ?Guardian
    {
        return Guardian::query()
            ->where('organization_id', $this->organization->id)
            ->where('user_id', $this->user->id)
            ->first();
    }
}
