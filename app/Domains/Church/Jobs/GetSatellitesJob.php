<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Church;
use App\Data\Models\Satellite;
use App\Domains\Church\DataObjects\SatelliteOptionData;
use App\Foundation\Jobs\BaseJob;

/** @extends BaseJob<list<SatelliteOptionData>> */
final class GetSatellitesJob extends BaseJob
{
    public function __construct(private readonly Church $church) {}

    /** @return list<SatelliteOptionData> */
    public function handle(): array
    {
        return array_values(Satellite::query()
            ->where('church_id', $this->church->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Satellite $s): SatelliteOptionData => SatelliteOptionData::fromModel($s))
            ->all());
    }
}
