<?php

declare(strict_types=1);

namespace App\Domains\Schedule\Jobs;

use App\Data\Models\Church;
use App\Data\Models\Schedule;
use App\Foundation\Jobs\BaseJob;

/**
 * The active classes (Schedules) of the asked-for satellites in this church, in one query, keyed by satellite id —
 * every asked-for satellite has a key, an empty list when it has no classes. Each list is in the order of the week:
 * weekly classes by day, then one-time classes by date (spec item 14), each then by start time, then name.
 *
 * @extends BaseJob<array<int, list<Schedule>>>
 */
final class GetSchedulesBySatelliteJob extends BaseJob
{
    /** @param list<int> $satelliteIds */
    public function __construct(
        private readonly Church $church,
        private readonly array $satelliteIds,
    ) {}

    /** @return array<int, list<Schedule>> */
    public function handle(): array
    {
        $schedules = Schedule::query()
            ->where('church_id', $this->church->id)
            ->whereIn('satellite_id', $this->satelliteIds)
            ->where('is_active', true)
            ->get()
            ->sortBy([
                fn (Schedule $a, Schedule $b): int => ($a->weekday?->isoNumber() ?? 8) <=> ($b->weekday?->isoNumber() ?? 8),
                fn (Schedule $a, Schedule $b): int => (string) $a->date?->toDateString() <=> (string) $b->date?->toDateString(),
                fn (Schedule $a, Schedule $b): int => $a->starts_at <=> $b->starts_at,
                fn (Schedule $a, Schedule $b): int => mb_strtolower($a->name) <=> mb_strtolower($b->name),
            ]);

        $bySatellite = [];
        foreach ($this->satelliteIds as $id) {
            $bySatellite[$id] = array_values($schedules->where('satellite_id', $id)->all());
        }

        return $bySatellite;
    }
}
