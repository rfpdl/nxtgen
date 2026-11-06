<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Church;
use App\Data\Models\SatelliteLink;
use App\Foundation\Jobs\BaseJob;

/**
 * A Link by id, ONLY within this church — an id from the address never reaches another church's — with its
 * satellite, for the scope check.
 *
 * @extends BaseJob<SatelliteLink>
 */
final class GetSatelliteLinkJob extends BaseJob
{
    public function __construct(
        private readonly Church $church,
        private readonly int $id,
    ) {}

    public function handle(): SatelliteLink
    {
        return SatelliteLink::query()
            ->where('church_id', $this->church->id)
            ->with('satellite')
            ->findOrFail($this->id);
    }
}
