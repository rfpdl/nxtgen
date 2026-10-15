<?php

declare(strict_types=1);

namespace App\Domains\User\Jobs;

use App\Data\Models\Guardian;
use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;

/**
 * Is this login used ONLY as one family's login, with no staff role anywhere, not a platform admin and not
 * confirmed yet? Only then may a church admin change its email (plan §4.4, v1 rule).
 *
 * @extends BaseJob<bool>
 */
final class GetUserIsFamilyOnlyJob extends BaseJob
{
    public function __construct(private readonly User $user) {}

    public function handle(): bool
    {
        return $this->user->email_verified_at === null
            && ! $this->user->is_platform_admin
            && ! RoleAssignment::query()->where('user_id', $this->user->id)->exists()
            && Guardian::query()->where('user_id', $this->user->id)->count() === 1;
    }
}
