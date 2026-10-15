<?php

declare(strict_types=1);

namespace App\Services\Org\Features\People;

use App\Domains\Organization\Jobs\GetChurchAdminCountJob;
use App\Domains\Organization\Jobs\GetRoleAssignmentJob;
use App\Domains\Organization\Jobs\UpdateRoleAssignmentAsInactiveJob;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use App\Services\Org\Features\BaseOrgFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Remove one role (D-R2). Same ceiling as giving it. The church is never left without a church admin — the
 * count is taken under a lock so two admins removing each other cannot both succeed.
 *
 * @extends BaseOrgFeature<RedirectResponse>
 */
final class RemoveStaffRoleFeature extends BaseOrgFeature
{
    public function __construct(private readonly int $assignmentId) {}

    public function handle(): RedirectResponse
    {
        $org = $this->organization();
        $scope = $this->scope();
        abort_unless($scope->canManagePeople(), 404);

        $assignment = $this->run(new GetRoleAssignmentJob(organization: $org, id: $this->assignmentId));
        abort_unless($assignment->is_active && $scope->canGrant($assignment->role, $assignment->scope_type, $assignment->scope_id), 404);

        $isChurchAdmin = $assignment->role === RoleEnum::Admin && $assignment->scope_type === ScopeTypeEnum::Organization;

        $removed = DB::transaction(function () use ($org, $assignment, $isChurchAdmin): bool {
            if ($isChurchAdmin && $this->run(new GetChurchAdminCountJob(organization: $org)) <= 1) {
                return false;
            }
            $this->run(new UpdateRoleAssignmentAsInactiveJob(assignment: $assignment));

            return true;
        });

        if (! $removed) {
            return back()->with('error', 'This is the only church admin. Add another church admin first.');
        }

        return back()->with('success', 'Role removed.');
    }
}
