<?php

declare(strict_types=1);

namespace App\Services\Platform\Features\People;

use App\Data\Models\RoleAssignment;
use App\Data\Models\Satellite;
use App\Data\Models\Schedule;
use App\Domains\Church\DataObjects\GrantOptionData;
use App\Domains\Church\DataObjects\RoleScope;
use App\Domains\Church\DataObjects\StaffMemberData;
use App\Domains\Church\DataObjects\StaffRoleData;
use App\Domains\Church\Jobs\GetSatellitesForChurchJob;
use App\Domains\Church\Jobs\GetStaffRolesJob;
use App\Domains\Schedule\Jobs\GetSchedulesByIdJob;
use App\Domains\Schedule\Jobs\GetSchedulesBySatelliteJob;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use App\Foundation\Attributes\UserStory;
use App\Services\Platform\Features\BasePlatformFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The platform's People (spec item 12): everyone who serves anywhere in the church with every Role, and every Role the
 * platform admin may give — Admin or Lifeshaper at any satellite, Admin at any active class.
 *
 * @extends BasePlatformFeature<Response>
 */
#[UserStory(
    title: 'See and give every Role',
    persona: 'platform admin',
    happyPaths: ['story#196' => 'The platform admin sees everyone who serves, at every Satellite, and gives any Role anywhere — a new Satellite\'s first admin too'],
)]
final class ShowPeopleFeature extends BasePlatformFeature
{
    public function handle(): Response
    {
        $church = $this->church();
        $satellites = $this->run(new GetSatellitesForChurchJob(church: $church));
        $ids = array_map(fn (Satellite $s): int => $s->id, $satellites);
        $names = array_combine($ids, array_map(fn (Satellite $s): string => $s->name, $satellites));

        $roles = $this->run(new GetStaffRolesJob(church: $church, satelliteIds: $ids));
        $classes = $this->run(new GetSchedulesByIdJob(church: $church, ids: array_values($roles
            ->filter(fn (RoleAssignment $a): bool => $a->scope_type === ScopeTypeEnum::Schedule)
            ->map(fn (RoleAssignment $a): int => $a->scope_id)->unique()->all())));

        $people = $roles->groupBy('user_id')->map(function ($held) use ($names, $classes): StaffMemberData {
            /** @var RoleAssignment $first */
            $first = $held->first();

            return new StaffMemberData(
                id: $first->user->id,
                name: $first->user->name,
                email: $first->user->email,
                roles: array_values($held->map(fn (RoleAssignment $a): StaffRoleData => StaffRoleData::fromModel($a, $names, $classes, canRemove: true))->all()),
            );
        })->sortBy(fn (StaffMemberData $p): string => mb_strtolower($p->name))->values();

        return Inertia::render('Platform/People', [
            'people' => $people->all(),
            'grant_options' => $this->grantOptions($satellites, $this->run(new GetSchedulesBySatelliteJob(church: $church, satelliteIds: $ids))),
        ]);
    }

    /**
     * Admin and Lifeshaper at each satellite, then Admin at each of its active classes.
     *
     * @param  list<Satellite>  $satellites
     * @param  array<int, list<Schedule>>  $classes  by satellite id
     * @return list<GrantOptionData>
     */
    private function grantOptions(array $satellites, array $classes): array
    {
        $options = [];
        foreach ($satellites as $satellite) {
            $at = RoleScope::of($satellite);
            foreach ([RoleEnum::Admin, RoleEnum::Lifeshaper] as $role) {
                $options[] = new GrantOptionData(label: StaffRoleData::label($role, $at, $satellite->name), role: $role, scope_type: $at->type, scope_id: $at->id);
            }
            foreach ($classes[$satellite->id] ?? [] as $class) {
                $place = RoleScope::of($class);
                $options[] = new GrantOptionData(label: StaffRoleData::label(RoleEnum::Admin, $place, "{$satellite->name} · {$class->name}"), role: RoleEnum::Admin, scope_type: $place->type, scope_id: $place->id);
            }
        }

        return $options;
    }
}
