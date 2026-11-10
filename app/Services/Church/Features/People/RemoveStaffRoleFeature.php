<?php

declare(strict_types=1);

namespace App\Services\Church\Features\People;

use App\Data\Models\RoleAssignment;
use App\Domains\Church\DataObjects\RoleScope;
use App\Domains\Schedule\Jobs\GetSchedulesByIdJob;
use App\Enums\AccessRowEnum;
use App\Enums\ScopeTypeEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Operations\Church\RemoveRoleOperation;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;

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

        $refused = $this->run(new RemoveRoleOperation(church: $church, assignment: $assignment));

        return $refused === null ? back()->with('success', 'Role removed.') : back()->with('error', $refused);
    }
}
