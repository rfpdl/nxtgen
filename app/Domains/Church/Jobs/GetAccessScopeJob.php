<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Church;
use App\Data\Models\RoleAssignment;
use App\Data\Models\Schedule;
use App\Data\Models\User;
use App\Domains\Church\DataObjects\AccessScope;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use App\Foundation\Jobs\BaseJob;

/** @extends BaseJob<AccessScope> */
final class GetAccessScopeJob extends BaseJob
{
    public function __construct(
        private readonly User $user,
        private readonly Church $church,
    ) {}

    public function handle(): AccessScope
    {
        $assignments = RoleAssignment::query()
            ->where('church_id', $this->church->id)
            ->where('user_id', $this->user->id)
            ->where('is_active', true)
            ->get();

        $ids = fn (RoleEnum $role, ScopeTypeEnum $type): array => array_values($assignments
            ->filter(fn (RoleAssignment $a): bool => $a->role === $role && $a->scope_type === $type)
            ->map(fn (RoleAssignment $a): int => $a->scope_id)
            ->all());

        return new AccessScope(
            churchId: $this->church->id,
            isChurchAdmin: $ids(RoleEnum::Admin, ScopeTypeEnum::Church) !== [],
            isChurchLifeshaper: $ids(RoleEnum::Lifeshaper, ScopeTypeEnum::Church) !== [],
            adminSatelliteIds: $ids(RoleEnum::Admin, ScopeTypeEnum::Satellite),
            lifeshaperSatelliteIds: $ids(RoleEnum::Lifeshaper, ScopeTypeEnum::Satellite),
            adminSchedules: $this->classesOf($ids(RoleEnum::Admin, ScopeTypeEnum::Schedule)),
        );
    }

    /**
     * A class admin's classes (TODO #9) with each one's satellite — this church's, still active: a retired class's
     * Role gives nothing.
     *
     * @param  list<int>  $scheduleIds
     * @return array<int, int> schedule id => satellite id
     */
    private function classesOf(array $scheduleIds): array
    {
        if ($scheduleIds === []) {
            return [];
        }

        $classes = [];
        foreach (Schedule::query()->where('church_id', $this->church->id)->whereIn('id', $scheduleIds)->where('is_active', true)->get(['id', 'satellite_id']) as $schedule) {
            $classes[$schedule->id] = $schedule->satellite_id;
        }

        return $classes;
    }
}
