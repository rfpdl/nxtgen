<?php

declare(strict_types=1);

namespace App\Domains\Schedule\Jobs;

use App\Data\Models\Satellite;
use App\Data\Models\Schedule;
use App\Enums\WeekdayEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * Add a class (Schedule) at a satellite. A name another ACTIVE class there has is refused by the database
 * (UniqueConstraintViolationException) — the Feature turns that into words on the name field.
 *
 * @extends BaseJob<Schedule>
 */
final class CreateScheduleJob extends BaseJob
{
    public function __construct(
        private readonly Satellite $satellite,
        private readonly string $name,
        private readonly ?WeekdayEnum $weekday,
        private readonly ?string $startsAt,
        private readonly ?string $endsAt,
    ) {}

    public function handle(): Schedule
    {
        return Schedule::query()->create([
            'church_id' => $this->satellite->church_id,
            'satellite_id' => $this->satellite->id,
            'name' => $this->name,
            'weekday' => $this->weekday,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
            'is_active' => true,
        ]);
    }
}
