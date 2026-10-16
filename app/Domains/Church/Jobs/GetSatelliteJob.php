<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Church;
use App\Data\Models\Satellite;
use App\Foundation\Jobs\BaseJob;

/**
 * A satellite by id, ONLY within this church — an id from a request body never reaches another church.
 *
 * @extends BaseJob<Satellite>
 */
final class GetSatelliteJob extends BaseJob
{
    public function __construct(
        private readonly Church $church,
        private readonly int $id,
    ) {}

    public function handle(): Satellite
    {
        return Satellite::query()->where('church_id', $this->church->id)->findOrFail($this->id);
    }
}
