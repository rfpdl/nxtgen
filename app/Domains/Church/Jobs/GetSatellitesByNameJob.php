<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Church;
use App\Data\Models\Satellite;
use App\Foundation\Jobs\BaseJob;

/**
 * This church's satellites keyed by their lower-cased name — how the registry sheet names a family's home
 * satellite ("CCF DXB", "house church").
 *
 * @extends BaseJob<array<string, Satellite>>
 */
final class GetSatellitesByNameJob extends BaseJob
{
    public function __construct(private readonly Church $church) {}

    /** @return array<string, Satellite> */
    public function handle(): array
    {
        return Satellite::query()
            ->where('church_id', $this->church->id)
            ->get()
            ->keyBy(fn (Satellite $s): string => mb_strtolower($s->name))
            ->all();
    }
}
