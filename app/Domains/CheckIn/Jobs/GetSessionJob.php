<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Data\Models\Schedule;
use App\Data\Models\Session;
use App\Foundation\Jobs\BaseJob;
use Carbon\CarbonImmutable;

/**
 * The class's Session on a date, if anyone has been checked in to it yet — looking at the door makes nothing.
 *
 * @extends BaseJob<Session|null>
 */
final class GetSessionJob extends BaseJob
{
    public function __construct(
        private readonly Schedule $schedule,
        private readonly CarbonImmutable $date,
    ) {}

    public function handle(): ?Session
    {
        return Session::query()
            ->where('church_id', $this->schedule->church_id)
            ->where('schedule_id', $this->schedule->id)
            ->whereDate('date', $this->date->toDateString())
            ->first();
    }
}
