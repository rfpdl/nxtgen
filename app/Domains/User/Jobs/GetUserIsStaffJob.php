<?php

declare(strict_types=1);

namespace App\Domains\User\Jobs;

use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;

/**
 * Does this login serve anywhere right now — an active role in any church, or the platform admin? Decides who
 * may have and use a backup password (plan D7: staff only).
 *
 * @extends BaseJob<bool>
 */
final class GetUserIsStaffJob extends BaseJob
{
    public function __construct(private readonly User $user) {}

    public function handle(): bool
    {
        return $this->user->is_platform_admin
            || RoleAssignment::query()->where('user_id', $this->user->id)->where('is_active', true)->exists();
    }
}
