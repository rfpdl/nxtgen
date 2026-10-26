<?php

declare(strict_types=1);

namespace App\Domains\Schedule\Jobs;

use App\Data\Models\Schedule;
use App\Foundation\Jobs\BaseJob;

/**
 * Retire a class (Schedule). The row stays (Sessions will point at it) with is_active off; the Classes page and
 * the door read active classes only, and the name is free for a new class.
 *
 * @extends BaseJob<Schedule>
 */
final class UpdateScheduleAsInactiveJob extends BaseJob
{
    public function __construct(private readonly Schedule $schedule) {}

    public function handle(): Schedule
    {
        $this->schedule->update(['is_active' => false]);

        return $this->schedule;
    }
}
