<?php

declare(strict_types=1);

namespace App\Domains\Schedule\Jobs;

use App\Data\Models\Schedule;
use App\Enums\WeekdayEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * Edit a class (Schedule): its name, weekly day or one-time date (the other null), and times. The satellite never changes — a class at another satellite is
 * a new class. A name another active class there has is refused by the database, as on create.
 *
 * @extends BaseJob<Schedule>
 */
final class UpdateScheduleJob extends BaseJob
{
    public function __construct(
        private readonly Schedule $schedule,
        private readonly string $name,
        private readonly ?WeekdayEnum $weekday,
        private readonly ?string $date,
        private readonly string $startsAt,
        private readonly string $endsAt,
    ) {}

    public function handle(): Schedule
    {
        $this->schedule->update([
            'name' => $this->name,
            'weekday' => $this->weekday,
            'date' => $this->date,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
        ]);

        return $this->schedule;
    }
}
