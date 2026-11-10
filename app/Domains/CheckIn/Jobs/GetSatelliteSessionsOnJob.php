<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Data\Models\Satellite;
use App\Data\Models\Session;
use App\Foundation\Jobs\BaseJob;
use Carbon\CarbonImmutable;

/**
 * A satellite's Sessions on a date — open or closed — of active classes, with their class. Serve lists each of today's
 * (spec item 17), though its class was moved to another day since it was opened (Codex).
 *
 * @extends BaseJob<list<Session>>
 */
final class GetSatelliteSessionsOnJob extends BaseJob
{
    public function __construct(
        private readonly Satellite $satellite,
        private readonly CarbonImmutable $date,
    ) {}

    /** @return list<Session> */
    public function handle(): array
    {
        return array_values(Session::query()
            ->where('church_id', $this->satellite->church_id)
            ->where('satellite_id', $this->satellite->id)
            ->whereDate('date', $this->date->toDateString())
            ->whereHas('schedule', fn ($q) => $q->where('is_active', true))
            ->with('schedule')
            ->get()
            ->all());
    }
}
