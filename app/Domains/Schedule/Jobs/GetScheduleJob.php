<?php

declare(strict_types=1);

namespace App\Domains\Schedule\Jobs;

use App\Data\Models\Church;
use App\Data\Models\Schedule;
use App\Foundation\Jobs\BaseJob;

/**
 * An active class (Schedule) by id, ONLY within this church — an id from the address never reaches another
 * church's class, and a retired class is gone.
 *
 * @extends BaseJob<Schedule>
 */
final class GetScheduleJob extends BaseJob
{
    public function __construct(
        private readonly Church $church,
        private readonly int $id,
    ) {}

    public function handle(): Schedule
    {
        return Schedule::query()
            ->where('church_id', $this->church->id)
            ->where('is_active', true)
            ->with('satellite')
            ->findOrFail($this->id);
    }
}
