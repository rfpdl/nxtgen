<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Church;
use App\Data\Models\SatelliteLink;
use App\Foundation\Jobs\BaseJob;

/**
 * The Links (GLOSSARY) of the asked-for satellites in this church, in one query, keyed by satellite id — every
 * asked-for satellite has a key, an empty list when it has none. Each list is in the admin's order.
 *
 * @extends BaseJob<array<int, list<SatelliteLink>>>
 */
final class GetSatelliteLinksJob extends BaseJob
{
    /** @param list<int> $satelliteIds */
    public function __construct(
        private readonly Church $church,
        private readonly array $satelliteIds,
    ) {}

    /** @return array<int, list<SatelliteLink>> */
    public function handle(): array
    {
        $links = SatelliteLink::query()
            ->where('church_id', $this->church->id)
            ->whereIn('satellite_id', $this->satelliteIds)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $bySatellite = [];
        foreach ($this->satelliteIds as $id) {
            $bySatellite[$id] = array_values($links->where('satellite_id', $id)->all());
        }

        return $bySatellite;
    }
}
