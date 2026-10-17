<?php

declare(strict_types=1);

namespace App\Services\Church\Features\People;

use App\Data\Models\RoleAssignment;
use App\Domains\Church\DataObjects\GrantOptionData;
use App\Domains\Church\DataObjects\RoleScope;
use App\Domains\Church\DataObjects\SatelliteOptionData;
use App\Domains\Church\DataObjects\StaffMemberData;
use App\Domains\Church\DataObjects\StaffRoleData;
use App\Domains\Church\Jobs\GetSatellitesJob;
use App\Domains\Church\Jobs\GetStaffRolesJob;
use App\Enums\AccessRowEnum;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Features\BaseChurchFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * People (D-R2): everyone who serves, with their roles, and the roles this viewer may give. A church admin
 * sees the whole church; a satellite admin sees their satellite's people (plan §3.2 "Grant roles").
 *
 * @extends BaseChurchFeature<Response>
 */
#[UserStory(
    title: 'See who serves',
    persona: 'church admin or satellite admin',
    happyPaths: ['story#74' => 'An Admin sees who serves and their Roles; a Satellite admin only their Satellite'],
    unhappyPaths: ['story#75' => 'Not there for a Lifeshaper'],
)]
#[AccessRow(AccessRowEnum::GrantRoles)]
final class ShowPeopleFeature extends BaseChurchFeature
{
    public function handle(): Response
    {
        $church = $this->church();
        $scope = $this->scope();
        abort_unless($scope->canManagePeople(), 404);

        $satellites = $this->run(new GetSatellitesJob(church: $church));
        $satelliteNames = [];
        foreach ($satellites as $satellite) {
            $satelliteNames[$satellite->id] = $satellite->name;
        }

        $roles = $this->run(new GetStaffRolesJob(church: $church, satelliteIds: $scope->isChurchAdmin ? null : $scope->adminSatelliteIds));

        $people = $roles->groupBy('user_id')->map(function ($held) use ($scope, $satelliteNames): StaffMemberData {
            /** @var RoleAssignment $first */
            $first = $held->first();

            return new StaffMemberData(
                id: $first->user->id,
                name: $first->user->name,
                email: $first->user->email,
                roles: array_values($held->map(fn (RoleAssignment $a): StaffRoleData => StaffRoleData::fromModel(
                    $a,
                    $satelliteNames,
                    canRemove: $scope->canGrant($a->role, RoleScope::ofAssignment($a)),
                ))->all()),
            );
        })->sortBy(fn (StaffMemberData $p): string => mb_strtolower($p->name))->values();

        return Inertia::render('Church/People/Index', [
            'people' => $people->all(),
            'grant_options' => $this->grantOptions($church->id, $satellites),
        ]);
    }

    /**
     * @param  list<SatelliteOptionData>  $satellites
     * @return list<GrantOptionData>
     */
    private function grantOptions(int $churchId, array $satellites): array
    {
        $places = [[new RoleScope(ScopeTypeEnum::Church, $churchId), null]];
        foreach ($satellites as $satellite) {
            $places[] = [new RoleScope(ScopeTypeEnum::Satellite, $satellite->id), $satellite->name];
        }

        $options = [];
        foreach ($places as [$place, $name]) {
            foreach ([RoleEnum::Admin, RoleEnum::Lifeshaper] as $role) {
                if ($this->scope()->canGrant($role, $place)) {
                    $options[] = new GrantOptionData(label: StaffRoleData::label($role, $place, $name), role: $role, scope_type: $place->type, scope_id: $place->id);
                }
            }
        }

        return $options;
    }
}
