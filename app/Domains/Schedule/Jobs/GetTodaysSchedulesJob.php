<?php

declare(strict_types=1);

namespace App\Domains\Schedule\Jobs;

use App\Data\Models\Church;
use App\Data\Models\Schedule;
use App\Enums\WeekdayEnum;
use App\Foundation\Jobs\BaseJob;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * The classes meeting `$today` (the church's date) at the asked-for satellites of this church (the address satellite,
 * or none) and `$alsoScheduleIds`, a class admin's own classes (TODO #9). A weekly class meets on its weekday, a
 * one-time class on its date only (spec item 14; Schedule::meetsOn). Active classes only, with their satellite, by
 * satellite name then start time then name.
 *
 * @extends BaseJob<list<Schedule>>
 */
final class GetTodaysSchedulesJob extends BaseJob
{
    /**
     * @param  list<int>  $satelliteIds
     * @param  list<int>  $alsoScheduleIds
     */
    public function __construct(
        private readonly Church $church,
        private readonly array $satelliteIds,
        private readonly CarbonImmutable $today,
        private readonly array $alsoScheduleIds = [],
    ) {}

    /** @return list<Schedule> */
    public function handle(): array
    {
        $schedules = Schedule::query()
            ->where('church_id', $this->church->id)
            ->where('is_active', true)
            ->where(fn (Builder $w) => $w
                ->whereIn('satellite_id', $this->satelliteIds)
                ->orWhereIn('id', $this->alsoScheduleIds))
            ->where(fn (Builder $q) => $q->where('weekday', WeekdayEnum::of($this->today)->value)->orWhereDate('date', $this->today->toDateString()))
            ->with('satellite')
            ->get()
            ->sortBy([
                fn (Schedule $a, Schedule $b): int => mb_strtolower($a->satellite->name) <=> mb_strtolower($b->satellite->name),
                fn (Schedule $a, Schedule $b): int => $a->starts_at <=> $b->starts_at,
                fn (Schedule $a, Schedule $b): int => mb_strtolower($a->name) <=> mb_strtolower($b->name),
            ]);

        return array_values($schedules->all());
    }
}
