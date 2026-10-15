<?php

declare(strict_types=1);

namespace App\Services\Org\Features\People;

use App\Data\Models\RoleAssignment;
use App\Domains\Organization\DataObjects\BranchOptionData;
use App\Domains\Organization\DataObjects\GrantOptionData;
use App\Domains\Organization\DataObjects\StaffMemberData;
use App\Domains\Organization\DataObjects\StaffRoleData;
use App\Domains\Organization\Jobs\GetBranchesJob;
use App\Domains\Organization\Jobs\GetStaffRolesJob;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use App\Services\Org\Features\BaseOrgFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * People (D-R2): everyone who serves, with their roles, and the roles this viewer may give. A church admin
 * sees the whole church; a satellite admin sees their satellite's people (plan §3.2 "Grant roles").
 *
 * @extends BaseOrgFeature<Response>
 */
final class ShowPeopleFeature extends BaseOrgFeature
{
    public function handle(): Response
    {
        $org = $this->organization();
        $scope = $this->scope();
        abort_unless($scope->canManagePeople(), 404);

        $branches = $this->run(new GetBranchesJob(organization: $org));
        $branchNames = [];
        foreach ($branches as $branch) {
            $branchNames[$branch->id] = $branch->name;
        }

        $roles = $this->run(new GetStaffRolesJob(organization: $org, branchIds: $scope->isChurchAdmin ? null : $scope->adminBranchIds));

        $people = $roles->groupBy('user_id')->map(function ($held) use ($scope, $branchNames): StaffMemberData {
            /** @var RoleAssignment $first */
            $first = $held->first();

            return new StaffMemberData(
                id: $first->user->id,
                name: $first->user->name,
                email: $first->user->email,
                roles: array_values($held->map(fn (RoleAssignment $a): StaffRoleData => StaffRoleData::fromModel(
                    $a,
                    $branchNames,
                    canRemove: $scope->canGrant($a->role, $a->scope_type, $a->scope_id),
                ))->all()),
            );
        })->sortBy(fn (StaffMemberData $p): string => mb_strtolower($p->name))->values();

        return Inertia::render('Org/People/Index', [
            'church' => ['name' => $org->name, 'slug' => $org->slug],
            'people' => $people->all(),
            'grantOptions' => $this->grantOptions($org->id, $branches),
        ]);
    }

    /**
     * @param  list<BranchOptionData>  $branches
     * @return list<GrantOptionData>
     */
    private function grantOptions(int $organizationId, array $branches): array
    {
        $places = [[ScopeTypeEnum::Organization, $organizationId, null]];
        foreach ($branches as $branch) {
            $places[] = [ScopeTypeEnum::Branch, $branch->id, $branch->name];
        }

        $options = [];
        foreach ($places as [$type, $id, $name]) {
            foreach ([RoleEnum::Admin, RoleEnum::Lifeshaper] as $role) {
                if ($this->scope()->canGrant($role, $type, $id)) {
                    $options[] = new GrantOptionData(label: StaffRoleData::label($role, $type, $name), role: $role, scopeType: $type, scopeId: $id);
                }
            }
        }

        return $options;
    }
}
