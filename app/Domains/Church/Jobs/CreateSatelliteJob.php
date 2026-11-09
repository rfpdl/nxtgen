<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Church;
use App\Data\Models\Satellite;
use App\Data\Models\Squad;
use App\Foundation\Jobs\BaseJob;
use App\Foundation\Support\SatelliteCode;

/**
 * Add a satellite to the church (spec item 12): its name, its address, a registration code nobody else has (made once,
 * never changed — spec item 7) and the chart's Squads, as the setup makes them.
 *
 * @extends BaseJob<Satellite>
 */
final class CreateSatelliteJob extends BaseJob
{
    public function __construct(
        private readonly Church $church,
        private readonly string $name,
        private readonly string $slug,
    ) {}

    public function handle(): Satellite
    {
        do {
            $code = SatelliteCode::random();
        } while (Satellite::query()->where('code', $code)->exists());

        $satellite = Satellite::query()->create(['church_id' => $this->church->id, 'name' => $this->name, 'slug' => $this->slug, 'code' => $code]);
        Squad::createChartFor($satellite);

        return $satellite;
    }
}
