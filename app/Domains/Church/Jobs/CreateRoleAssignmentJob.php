<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Church;
use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * Give a role. A removed role is switched back on (the row stays unique per user+role+scope, plan §4.1).
 * Returns false when the person already had it, so the page can say so instead of sending another email.
 *
 * @extends BaseJob<bool>
 */
final class CreateRoleAssignmentJob extends BaseJob
{
    public function __construct(
        private readonly Church $church,
        private readonly User $user,
        private readonly RoleEnum $role,
        private readonly ScopeTypeEnum $scopeType,
        private readonly int $scopeId,
        private readonly User $grantedBy,
    ) {}

    public function handle(): bool
    {
        $assignment = RoleAssignment::query()->lockForUpdate()->firstOrNew([
            'church_id' => $this->church->id,
            'user_id' => $this->user->id,
            'role' => $this->role,
            'scope_type' => $this->scopeType,
            'scope_id' => $this->scopeId,
        ]);

        if ($assignment->exists && $assignment->is_active) {
            return false;
        }

        $assignment->fill([
            'is_active' => true,
            'granted_by' => $this->grantedBy->id,
        ])->save();

        return true;
    }
}
