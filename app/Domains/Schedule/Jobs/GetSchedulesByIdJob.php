<?php

declare(strict_types=1);

namespace App\Domains\Schedule\Jobs;

use App\Data\Models\Church;
use App\Data\Models\Schedule;
use App\Foundation\Jobs\BaseJob;

/**
 * Classes (Schedules) by id, ONLY within this church, keyed by id, with their satellite — retired ones too: a class
 * admin's Role (TODO #9) is named and removed on People after its class is retired.
 *
 * @extends BaseJob<array<int, Schedule>>
 */
final class GetSchedulesByIdJob extends BaseJob
{
    /** @param list<int> $ids */
    public function __construct(
        private readonly Church $church,
        private readonly array $ids,
    ) {}

    /** @return array<int, Schedule> */
    public function handle(): array
    {
        if ($this->ids === []) {
            return [];
        }

        $found = [];
        foreach (Schedule::query()->where('church_id', $this->church->id)->whereIn('id', $this->ids)->with('satellite')->get() as $schedule) {
            $found[$schedule->id] = $schedule;
        }

        return $found;
    }
}
