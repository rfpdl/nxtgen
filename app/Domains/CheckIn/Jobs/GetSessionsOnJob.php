<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Data\Models\Schedule;
use App\Data\Models\Session;
use App\Foundation\Jobs\BaseJob;
use Carbon\CarbonImmutable;

/**
 * Each listed class's Session on a date, in one query, keyed by class (Schedule) id — a class nobody opened that day
 * has none (spec item 15). Serve's first page, so its classes are not asked one by one.
 *
 * @extends BaseJob<array<int, Session>>
 */
final class GetSessionsOnJob extends BaseJob
{
    /** @param list<Schedule> $classes */
    public function __construct(
        private readonly array $classes,
        private readonly CarbonImmutable $date,
    ) {}

    /** @return array<int, Session> */
    public function handle(): array
    {
        if ($this->classes === []) {
            return [];
        }

        $sessions = [];
        foreach (Session::query()
            ->whereIn('church_id', array_unique(array_map(fn (Schedule $s): int => $s->church_id, $this->classes)))
            ->whereIn('schedule_id', array_map(fn (Schedule $s): int => $s->id, $this->classes))
            ->whereDate('date', $this->date->toDateString())
            ->get() as $session) {
            $sessions[$session->schedule_id] = $session;
        }

        return $sessions;
    }
}
