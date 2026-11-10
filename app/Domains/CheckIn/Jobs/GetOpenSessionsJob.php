<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Data\Models\Satellite;
use App\Data\Models\Session;
use App\Foundation\Jobs\BaseJob;
use Carbon\CarbonImmutable;

/**
 * A satellite's Sessions open on `$today` (spec item 15: its date, not closed) of active classes, with their class and satellite, by start time then
 * name — the bar's dropdown on a class's page (Roy 2026-11-10). WHICH of them a person may open is the caller's.
 *
 * @extends BaseJob<list<Session>>
 */
final class GetOpenSessionsJob extends BaseJob
{
    public function __construct(
        private readonly Satellite $satellite,
        private readonly CarbonImmutable $today,
    ) {}

    /** @return list<Session> */
    public function handle(): array
    {
        return array_values(Session::query()
            ->where('church_id', $this->satellite->church_id)
            ->where('satellite_id', $this->satellite->id)
            ->whereDate('date', $this->today->toDateString())
            ->whereNull('closed_at')
            // A class retired while open is no longer anyone's to go to (review, issue 13).
            ->whereHas('schedule', fn ($q) => $q->where('is_active', true))
            ->with(['schedule', 'satellite'])
            ->get()
            ->sortBy([
                fn (Session $a, Session $b): int => $a->schedule->starts_at <=> $b->schedule->starts_at,
                fn (Session $a, Session $b): int => mb_strtolower($a->schedule->name) <=> mb_strtolower($b->schedule->name),
            ])
            ->all());
    }
}
