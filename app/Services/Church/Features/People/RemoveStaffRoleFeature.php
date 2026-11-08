<?php

declare(strict_types=1);

namespace App\Services\Church\Features\People;

use App\Data\Models\RoleAssignment;
use App\Domains\Church\DataObjects\RoleScope;
use App\Domains\Church\Jobs\GetChurchAdminCountJob;
use App\Domains\Church\Jobs\UpdateRoleAssignmentAsInactiveJob;
use App\Domains\Schedule\Jobs\GetSchedulesByIdJob;
use App\Enums\AccessRowEnum;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Remove one role (D-R2). Same ceiling as giving it. The church is never left without a church admin — the
 * count is taken under a lock so two admins removing each other cannot both succeed.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Remove someone\'s Role',
    persona: 'church admin or satellite admin',
    happyPaths: ['story#78' => 'An Admin switches a Role off, and it is kept for the record'],
    unhappyPaths: ['story#79' => 'Never the last church admin, a Role above their own, or another church\'s'],
)]
#[AccessRow(AccessRowEnum::GrantRoles)]
final class RemoveStaffRoleFeature extends BaseChurchFeature
{
    public function __construct(private readonly RoleAssignment $assignment) {}

    public function handle(): RedirectResponse
    {
        $church = $this->church();
        $scope = $this->scope();
        abort_unless($scope->canManagePeople(), 404);

        $assignment = $this->assignment;
        // A class admin's Role (TODO #9) is removed by whoever may give it: the class's satellite says who.
        $class = $assignment->scope_type === ScopeTypeEnum::Schedule
            ? $this->run(new GetSchedulesByIdJob(church: $church, ids: [$assignment->scope_id]))[$assignment->scope_id] ?? null
            : null;
        abort_unless($assignment->is_active && $scope->canGrant($assignment->role, RoleScope::ofAssignment($assignment, $class)), 404);

        $isChurchAdmin = $assignment->role === RoleEnum::Admin && $assignment->scope_type === ScopeTypeEnum::Church;

        $removed = DB::transaction(function () use ($church, $assignment, $isChurchAdmin): bool {
            if ($isChurchAdmin && $this->run(new GetChurchAdminCountJob(church: $church)) <= 1) {
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
