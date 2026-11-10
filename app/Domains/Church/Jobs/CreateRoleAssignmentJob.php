<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\RoleAssignment;
use App\Data\Models\Satellite;
use App\Data\Models\Schedule;
use App\Data\Models\User;
use App\Domains\Church\DataObjects\RoleScope;
use App\Enums\RoleEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * Give a role. A removed role is switched back on (the row stays unique per user+role+scope, plan §4.1).
 * Returns false when the person already had it, so the page can say so instead of sending another email.
 *
 * The place is the model the role is held at — one Satellite or one class (RoleScope says which; spec item 9: no
 * church-wide Role), so a role can only ever land in its place's own church.
 *
 * @extends BaseJob<bool>
 */
final class CreateRoleAssignmentJob extends BaseJob
{
    public function __construct(
        private readonly User $user,
        private readonly RoleEnum $role,
        private readonly Satellite|Schedule $place,
        private readonly User $grantedBy,
    ) {}

    public function handle(): bool
    {
        $scope = RoleScope::of($this->place);
        $assignment = RoleAssignment::query()->lockForUpdate()->firstOrNew([
            'church_id' => $this->place->church_id,
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
