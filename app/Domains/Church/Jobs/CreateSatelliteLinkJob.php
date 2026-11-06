<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Satellite;
use App\Data\Models\SatelliteLink;
use App\Foundation\Jobs\BaseJob;

/**
 * Add a Link to a satellite's poster, last in its order. How many it may hold is the Feature's
 * (SatelliteLink::MAX_PER_SATELLITE).
 *
 * @extends BaseJob<SatelliteLink>
 */
final class CreateSatelliteLinkJob extends BaseJob
{
    public function __construct(
        private readonly Satellite $satellite,
        private readonly string $label,
        private readonly string $url,
    ) {}

    public function handle(): SatelliteLink
    {
        $last = SatelliteLink::query()
            ->where('church_id', $this->satellite->church_id)
            ->where('satellite_id', $this->satellite->id)
            ->max('position');

        return SatelliteLink::query()->create([
            'church_id' => $this->satellite->church_id,
            'satellite_id' => $this->satellite->id,
            'label' => $this->label,
            'url' => $this->url,
            'position' => (is_numeric($last) ? (int) $last : 0) + 1,
        ]);
    }
}
