<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Data\Models\Church;
use App\Data\Models\Schedule;
use App\Data\Models\Session;
use App\Foundation\Jobs\BaseJob;

/**
 * The classes Attendance can report on, keyed by satellite id (every asked-for satellite has a key): the active ones
 * in the order of the week (by day — no day last — then start time, then name), then the retired ones that have a
 * Session, by name, so their history stays reportable. A retired class that never met is left out.
 *
 * @extends BaseJob<array<int, list<Schedule>>>
 */
final class GetReportableSchedulesJob extends BaseJob
{
    /** @param list<int> $satelliteIds */
    public function __construct(
        private readonly Church $church,
        private readonly array $satelliteIds,
    ) {}

    /** @return array<int, list<Schedule>> */
    public function handle(): array
    {
        $met = Session::query()->where('church_id', $this->church->id)->select('schedule_id');
        $schedules = Schedule::query()
            ->where('church_id', $this->church->id)
            ->whereIn('satellite_id', $this->satelliteIds)
            ->where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', $met))
            ->get()
            ->sortBy([
                fn (Schedule $a, Schedule $b): int => $b->is_active <=> $a->is_active,
                fn (Schedule $a, Schedule $b): int => ($a->is_active ? ($a->weekday?->isoNumber() ?? 8) : 0) <=> ($b->is_active ? ($b->weekday?->isoNumber() ?? 8) : 0),
                fn (Schedule $a, Schedule $b): int => ($a->is_active ? ($a->starts_at ?? '99') : '') <=> ($b->is_active ? ($b->starts_at ?? '99') : ''),
                fn (Schedule $a, Schedule $b): int => mb_strtolower($a->name) <=> mb_strtolower($b->name),
            ]);

        $bySatellite = [];
        foreach ($this->satelliteIds as $id) {
            $bySatellite[$id] = array_values($schedules->where('satellite_id', $id)->all());
        }

        return $bySatellite;
    }
}
