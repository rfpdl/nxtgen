<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Data\Models\Schedule;
use App\Data\Models\Session;
use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;
use App\Foundation\Support\SatelliteCode;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * The class's Session on a date, made open — with its code, its address (spec item 16) — when an admin opens the class (spec item 15) — or the one already there,
 * as it is (reopening is UpdateSessionAsOpenJob). The unique (schedule, date) key decides when two admins make it at
 * once: one inserts, the other reads that row with a LOCKING read (a plain read inside the caller's transaction sees
 * only its own snapshot, which cannot hold the other's new row; the locking read waits for its commit).
 *
 * @extends BaseJob<Session>
 */
final class CreateSessionJob extends BaseJob
{
    public function __construct(
        private readonly Schedule $schedule,
        private readonly CarbonImmutable $date,
        private readonly ?User $by,
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
                'code' => $this->freeCode(), 'opened_at' => now(), 'opened_by' => $this->by?->id,
            ]));
        } catch (UniqueConstraintViolationException) {
            // The same class and date opened at the same moment (a code taken meanwhile is one in 10^11: it fails loudly).
            return Session::query()->where('church_id', $church)->where('schedule_id', $this->schedule->id)->whereDate('date', $date)->lockForUpdate()->firstOrFail();
        }
    }

    /** A code no Session has (spec item 16): the same kind as a satellite's registration code. */
    private function freeCode(): string
    {
        do {
            $code = SatelliteCode::random();
        } while (Session::query()->where('code', $code)->exists());

        return $code;
    }
}
