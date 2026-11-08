<?php

declare(strict_types=1);

namespace App\Services\Church\Features\People;

use App\Data\Models\RoleAssignment;
use App\Data\Models\Schedule;
use App\Domains\Church\DataObjects\GrantOptionData;
use App\Domains\Church\DataObjects\RoleScope;
use App\Domains\Church\DataObjects\SatelliteOptionData;
use App\Domains\Church\DataObjects\StaffMemberData;
use App\Domains\Church\DataObjects\StaffRoleData;
use App\Domains\Church\Jobs\GetStaffRolesJob;
use App\Domains\Schedule\Jobs\GetSchedulesByIdJob;
use App\Domains\Schedule\Jobs\GetSchedulesBySatelliteJob;
use App\Enums\AccessRowEnum;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Features\BaseChurchFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * People (D-R2): everyone who serves, with their roles, and the roles this viewer may give: a satellite admin
 * sees their satellite's people and its classes' (plan §3.2 "Grant roles"; spec item 9).
 *
 * @extends BaseChurchFeature<Response>
 */
#[UserStory(
    title: 'See who serves',
    persona: 'satellite admin',
    happyPaths: ['story#74' => 'A Satellite admin sees who serves at their Satellite and their Roles'],
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

        // This satellite's People (spec item 9): its Roles and its classes' — never another satellite's.
        $here = $this->satellite();
        $satellites = [SatelliteOptionData::fromModel($here)];
        $satelliteNames = [$here->id => $here->name];

        $roles = $this->run(new GetStaffRolesJob(church: $church, satelliteIds: [$here->id]));
        // Class admins' classes (TODO #9), retired ones too, to name each Role and to ask who may remove it.
        $classes = $this->run(new GetSchedulesByIdJob(church: $church, ids: array_values($roles
            ->filter(fn (RoleAssignment $a): bool => $a->scope_type === ScopeTypeEnum::Schedule)
            ->map(fn (RoleAssignment $a): int => $a->scope_id)->unique()->all())));

        $people = $roles->groupBy('user_id')->map(function ($held) use ($scope, $satelliteNames, $classes): StaffMemberData {
            /** @var RoleAssignment $first */
            $first = $held->first();

            return new StaffMemberData(
                id: $first->user->id,
                name: $first->user->name,
                email: $first->user->email,
                roles: array_values($held->map(fn (RoleAssignment $a): StaffRoleData => StaffRoleData::fromModel(
                    $a,
                    $satelliteNames,
                    $classes,
                    canRemove: $scope->canGrant($a->role, RoleScope::ofAssignment($a, $classes[$a->scope_id] ?? null)),
                ))->all()),
            );
        })->sortBy(fn (StaffMemberData $p): string => mb_strtolower($p->name))->values();

        return Inertia::render('Church/People/Index', [
            'people' => $people->all(),
            'grant_options' => $this->grantOptions($satellites, $this->run(new GetSchedulesBySatelliteJob(
                church: $church,
                satelliteIds: array_map(fn (SatelliteOptionData $s): int => $s->id, $satellites),
            ))),
        ]);
    }

    /**
     * The Roles this admin may give: at this satellite, and Admin at each of its active classes (TODO #9).
     *
     * @param  list<SatelliteOptionData>  $satellites
     * @param  array<int, list<Schedule>>  $classes  by satellite id
     * @return list<GrantOptionData>
     */
    private function grantOptions(array $satellites, array $classes): array
    {
        $places = [];
        foreach ($satellites as $satellite) {
            $places[] = [new RoleScope(ScopeTypeEnum::Satellite, $satellite->id), $satellite->name];
            foreach ($classes[$satellite->id] ?? [] as $class) {
                $places[] = [RoleScope::of($class), "{$satellite->name} · {$class->name}"];
            }
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
