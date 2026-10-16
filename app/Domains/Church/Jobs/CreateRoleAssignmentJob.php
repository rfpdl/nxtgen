<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Church;
use App\Data\Models\RoleAssignment;
use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Domains\Church\DataObjects\RoleScope;
use App\Enums\RoleEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * Give a role. A removed role is switched back on (the row stays unique per user+role+scope, plan §4.1).
 * Returns false when the person already had it, so the page can say so instead of sending another email.
 *
 * The place is the model the role is held at — the whole Church or one Satellite (RoleScope says which, and whose
 * church), so a satellite role can only ever land in that satellite's church.
 *
 * @extends BaseJob<bool>
 */
final class CreateRoleAssignmentJob extends BaseJob
{
    public function __construct(
        private readonly User $user,
        private readonly RoleEnum $role,
        private readonly Church|Satellite $place,
        private readonly User $grantedBy,
    ) {}

    public function handle(): bool
    {
        $scope = RoleScope::of($this->place);
        $assignment = RoleAssignment::query()->lockForUpdate()->firstOrNew([
            'church_id' => RoleScope::churchIdOf($this->place),
            'user_id' => $this->user->id,
            'role' => $this->role,
            'scope_type' => $scope->type,
            'scope_id' => $scope->id,
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
