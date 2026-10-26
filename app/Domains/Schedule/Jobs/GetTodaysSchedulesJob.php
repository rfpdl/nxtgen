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
 * The classes meeting `$today` (the church's date) at the asked-for satellites of this church — null = every
 * satellite (a church-wide Role). A class with a day meets only on that day; one with no day meets every day
 * (DECISIONS 2026-10-26). Active classes only, with their satellite, by satellite name then start time (a class with
 * no time last) then name.
 *
 * @extends BaseJob<list<Schedule>>
 */
final class GetTodaysSchedulesJob extends BaseJob
{
    /** @param list<int>|null $satelliteIds */
    public function __construct(
        private readonly Church $church,
        private readonly ?array $satelliteIds,
        private readonly CarbonImmutable $today,
    ) {}

    /** @return list<Schedule> */
    public function handle(): array
    {
        $schedules = Schedule::query()
            ->where('church_id', $this->church->id)
            ->where('is_active', true)
            ->when($this->satelliteIds !== null, fn (Builder $q) => $q->whereIn('satellite_id', $this->satelliteIds ?? []))
            ->where(fn (Builder $q) => $q->whereNull('weekday')->orWhere('weekday', WeekdayEnum::of($this->today)->value))
            ->with('satellite')
            ->get()
            ->sortBy([
                fn (Schedule $a, Schedule $b): int => mb_strtolower($a->satellite->name) <=> mb_strtolower($b->satellite->name),
                fn (Schedule $a, Schedule $b): int => ($a->starts_at ?? '99') <=> ($b->starts_at ?? '99'),
                fn (Schedule $a, Schedule $b): int => mb_strtolower($a->name) <=> mb_strtolower($b->name),
            ]);

        return array_values($schedules->all());
    }
}
