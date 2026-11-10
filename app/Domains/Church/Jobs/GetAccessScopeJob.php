<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\RoleAssignment;
use App\Data\Models\Satellite;
use App\Data\Models\Schedule;
use App\Data\Models\User;
use App\Domains\Church\DataObjects\AccessScope;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * What one person may do at ONE satellite — the one in the address (spec item 10, Roy 2026-11-07): only their Roles
 * there count — at the satellite, or at one of its classes — so a page shows that satellite and nothing else
 * (DECISIONS 2026-11-08).
 *
 * @extends BaseJob<AccessScope>
 */
final class GetAccessScopeJob extends BaseJob
{
    public function __construct(
        private readonly User $user,
        private readonly Satellite $satellite,
    ) {}

    public function handle(): AccessScope
    {
        $assignments = RoleAssignment::query()
            ->where('church_id', $this->satellite->church_id)
            ->where('user_id', $this->user->id)
            ->where('is_active', true)
            ->get();

        $has = fn (RoleEnum $role, ScopeTypeEnum $type, ?int $id = null): bool => $assignments
            ->contains(fn (RoleAssignment $a): bool => $a->role === $role && $a->scope_type === $type && ($id === null || $a->scope_id === $id));
        $here = fn (RoleEnum $role): array => $has($role, ScopeTypeEnum::Satellite, $this->satellite->id) ? [$this->satellite->id] : [];
        // The platform admin (spec item 12) is an admin at every satellite, with no Role — so on no satellite's People.
        $admin = $this->user->is_platform_admin ? [$this->satellite->id] : $here(RoleEnum::Admin);

        return new AccessScope(
            churchId: $this->satellite->church_id,
            adminSatelliteIds: $admin,
            lifeshaperSatelliteIds: $here(RoleEnum::Lifeshaper),
            adminSchedules: $this->classesHere(array_values($assignments
                ->filter(fn (RoleAssignment $a): bool => $a->role === RoleEnum::Admin && $a->scope_type === ScopeTypeEnum::Schedule)
                ->map(fn (RoleAssignment $a): int => $a->scope_id)
                ->all())),
        );
    }

    /**
     * A class admin's classes (TODO #9) at this satellite, still active: a retired class's Role gives nothing.
     *
     * @param  list<int>  $scheduleIds
     * @return array<int, int> schedule id => satellite id
     */
    private function classesHere(array $scheduleIds): array
    {
        if ($scheduleIds === []) {
            return [];
        }

        $classes = [];
        foreach (Schedule::query()->where('church_id', $this->satellite->church_id)->where('satellite_id', $this->satellite->id)->whereIn('id', $scheduleIds)->where('is_active', true)->get(['id', 'satellite_id']) as $schedule) {
            $classes[$schedule->id] = $schedule->satellite_id;
        }

        return $classes;
    }
}
