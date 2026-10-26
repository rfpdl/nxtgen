<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Data\Models\Church;
use App\Data\Models\Schedule;
use App\Data\Models\Session;
use App\Domains\CheckIn\DataObjects\AttendanceSelectionData;
use App\Foundation\Jobs\BaseJob;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Builder;

/**
 * Which Session Attendance shows (DECISIONS 2026-10-26). What the address picks — a satellite (then its first
 * class), a class (then its satellite), a date (Y-m-d) — each checked against what this admin is offered: a
 * satellite or class not offered, a class not at the picked satellite, or a date that is not one, is a 404.
 *
 * With nothing picked: a Session today at the offered satellites (the first satellite that has one, its earliest
 * class), else the latest Session up to today at the first satellite, else that satellite's first class today.
 *
 * @extends BaseJob<AttendanceSelectionData>
 */
final class GetAttendanceSelectionJob extends BaseJob
{
    /**
     * @param  list<int>  $satelliteIds  the satellites offered, in the picker's order
     * @param  array<int, list<Schedule>>  $schedules  the classes offered, by satellite (GetReportableSchedulesJob)
     */
    public function __construct(
        private readonly Church $church,
        private readonly array $satelliteIds,
        private readonly array $schedules,
        private readonly ?int $satellite,
        private readonly ?int $class,
        private readonly ?string $date,
        private readonly CarbonImmutable $today,
    ) {}

    public function handle(): AttendanceSelectionData
    {
        abort_if($this->satellite !== null && ! in_array($this->satellite, $this->satelliteIds, true), 404);
        $schedule = $this->class === null ? null : $this->offered($this->class);
        abort_if($schedule !== null && $this->satellite !== null && $schedule->satellite_id !== $this->satellite, 404);
        $date = $this->date === null ? $this->today : $this->parsed($this->date);

        if ($this->satellite === null && $schedule === null && $this->date === null) {
            $default = $this->defaultSession();
            if ($default !== null) {
                return new AttendanceSelectionData(satelliteId: $default->satellite_id, schedule: $this->offered($default->schedule_id), date: $default->date);
            }
        }

        $satelliteId = $this->satellite ?? $schedule->satellite_id ?? ($this->satelliteIds[0] ?? null);
        $schedule ??= $satelliteId === null ? null : ($this->schedules[$satelliteId][0] ?? null);

        return new AttendanceSelectionData(satelliteId: $satelliteId, schedule: $schedule, date: $date);
    }

    /** The offered class with this id — a 404 when it is not offered. */
    private function offered(int $id): Schedule
    {
        foreach ($this->schedules as $classes) {
            foreach ($classes as $class) {
                if ($class->id === $id) {
                    return $class;
                }
            }
        }

        abort(404);
    }

    private function parsed(string $text): CarbonImmutable
    {
        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $text, $this->today->getTimezone());
        } catch (InvalidFormatException) {
            $date = null;
        }
        abort_unless($date instanceof CarbonImmutable && $date->toDateString() === $text, 404);

        return $date;
    }

    private function defaultSession(): ?Session
    {
        if ($this->satelliteIds === []) {
            return null;
        }

        $today = $this->sessions()->whereIn('satellite_id', $this->satelliteIds)->whereDate('date', $this->today->toDateString())->get()
            ->sortBy([
                fn (Session $a, Session $b): int => array_search($a->satellite_id, $this->satelliteIds, true) <=> array_search($b->satellite_id, $this->satelliteIds, true),
                fn (Session $a, Session $b): int => ($a->schedule->starts_at ?? '99') <=> ($b->schedule->starts_at ?? '99'),
                fn (Session $a, Session $b): int => $a->id <=> $b->id,
            ])
            ->first();

        return $today ?? $this->sessions()
            ->where('satellite_id', $this->satelliteIds[0])
            ->whereDate('date', '<=', $this->today->toDateString())
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->first();
    }

    /** @return Builder<Session> this church's Sessions of the offered classes */
    private function sessions(): Builder
    {
        $offered = array_map(fn (Schedule $s): int => $s->id, array_merge(...array_values($this->schedules)));

        return Session::query()->where('church_id', $this->church->id)->whereIn('schedule_id', $offered)->with('schedule');
    }
}
