<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Data\Models\Schedule;
use App\Data\Models\Session;
use App\Foundation\Jobs\BaseJob;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * The class's Session on a date, made by the first Check-in (DECISIONS 2026-10-26: never opened or closed) — or the
 * one already there. The unique (schedule, date) key decides when two phones make it at once: one inserts, the other
 * reads that row with a LOCKING read (a plain read inside the caller's transaction sees only its own snapshot, which
 * cannot hold the other phone's new row; the locking read waits for its commit).
 *
 * @extends BaseJob<Session>
 */
final class CreateSessionJob extends BaseJob
{
    public function __construct(
        private readonly Schedule $schedule,
        private readonly CarbonImmutable $date,
    ) {}

    public function handle(): Session
    {
        $church = $this->schedule->church_id;
        $date = $this->date->toDateString();
        // A plain read first (no lock: a locking read of a missing row takes a gap lock that two phones then both
        // insert into — a deadlock). A row committed before this transaction began is seen here.
        $existing = Session::query()->where('church_id', $church)->where('schedule_id', $this->schedule->id)->whereDate('date', $date)->first();
        if ($existing !== null) {
            return $existing;
        }

        try {
            return Session::query()->withSavepointIfNeeded(fn (): Session => Session::query()->create([
                'church_id' => $church, 'satellite_id' => $this->schedule->satellite_id, 'schedule_id' => $this->schedule->id, 'date' => $date,
            ]));
        } catch (UniqueConstraintViolationException) {
            return Session::query()->where('church_id', $church)->where('schedule_id', $this->schedule->id)->whereDate('date', $date)->lockForUpdate()->firstOrFail();
        }
    }
}
