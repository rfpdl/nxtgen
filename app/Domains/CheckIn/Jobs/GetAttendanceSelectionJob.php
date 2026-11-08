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
 * Which Session Attendance shows (DECISIONS 2026-10-26), at the satellite in the address (DECISIONS 2026-11-08: there
 * is no satellite to pick). What the address picks — a class, a date (Y-m-d) — each checked against what this admin is
 * offered there: a class not offered, or a date that is not one, is a 404.
 *
 * With nothing picked: a Session today of an offered class (the earliest class), else the latest Session up to today,
 * else the first class today.
 *
 * @extends BaseJob<AttendanceSelectionData>
 */
final class GetAttendanceSelectionJob extends BaseJob
{
    /** @param  list<Schedule>  $schedules  the classes offered at the address satellite (GetReportableSchedulesJob) */
    public function __construct(
        private readonly Church $church,
        private readonly array $schedules,
        private readonly ?int $class,
        private readonly ?string $date,
        private readonly CarbonImmutable $today,
    ) {}

    public function handle(): AttendanceSelectionData
    {
        $schedule = $this->class === null ? null : $this->offered($this->class);
        $date = $this->date === null ? $this->today : $this->parsed($this->date);

        if ($schedule === null && $this->date === null) {
            $default = $this->defaultSession();
            if ($default !== null) {
                return new AttendanceSelectionData(schedule: $this->offered($default->schedule_id), date: $default->date);
            }
        }

        return new AttendanceSelectionData(schedule: $schedule ?? ($this->schedules[0] ?? null), date: $date);
    }

    /** The offered class with this id — a 404 when it is not offered. */
    private function offered(int $id): Schedule
    {
        foreach ($this->schedules as $class) {
            if ($class->id === $id) {
                return $class;
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
        if ($this->schedules === []) {
            return null;
        }

        $today = $this->sessions()->whereDate('date', $this->today->toDateString())->get()
            ->sortBy([
                fn (Session $a, Session $b): int => ($a->schedule->starts_at ?? '99') <=> ($b->schedule->starts_at ?? '99'),
                fn (Session $a, Session $b): int => $a->id <=> $b->id,
            ])
            ->first();

        return $today ?? $this->sessions()
            ->whereDate('date', '<=', $this->today->toDateString())
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->first();
    }

    /** @return Builder<Session> this church's Sessions of the offered classes */
    private function sessions(): Builder
    {
        $offered = array_map(fn (Schedule $s): int => $s->id, $this->schedules);

        return Session::query()->where('church_id', $this->church->id)->whereIn('schedule_id', $offered)->with('schedule');
    }
}
