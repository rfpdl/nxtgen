<?php

declare(strict_types=1);

namespace App\Services\Church\Features\People;

use App\Data\Models\RoleAssignment;
use App\Domains\Church\DataObjects\RoleScope;
use App\Domains\Church\Jobs\GetSatelliteAdminCountJob;
use App\Domains\Church\Jobs\GetSatelliteJob;
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
 * Remove one role (D-R2). Same ceiling as giving it. A satellite is never left without an admin — the count is
 * taken under the satellite's lock so two admins removing each other cannot both succeed.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Remove someone\'s Role',
    persona: 'satellite admin',
    happyPaths: ['story#78' => 'An Admin switches a Role off, and it is kept for the record'],
    unhappyPaths: ['story#79' => 'Never the last admin of a satellite, a Role at another satellite, or another church\'s'],
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

        // The last admin of a satellite stays (spec item 9): counted under the satellite's lock, so two admins removing
        // each other at once cannot both go.
        $satellite = $assignment->role === RoleEnum::Admin && $assignment->scope_type === ScopeTypeEnum::Satellite
            ? $this->run(new GetSatelliteJob(church: $church, id: $assignment->scope_id))
            : null;

        $removed = DB::transaction(function () use ($assignment, $satellite): bool {
            if ($satellite !== null && $this->run(new GetSatelliteAdminCountJob(satellite: $satellite)) <= 1) {
                return false;
            }
            $this->run(new UpdateRoleAssignmentAsInactiveJob(assignment: $assignment));

            return true;
        });

        if (! $removed) {
            return back()->with('error', "This is the only admin of {$satellite?->name}. Add another admin there first.");
        }

        return back()->with('success', 'Role removed.');
    }
}
