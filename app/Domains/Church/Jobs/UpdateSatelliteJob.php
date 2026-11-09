<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Satellite;
use App\Foundation\Jobs\BaseJob;

/**
 * Rename a satellite or move its address (spec item 12). Its registration code stays: printed QR posters still find it
 * (spec item 7).
 *
 * @extends BaseJob<Satellite>
 */
final class UpdateSatelliteJob extends BaseJob
{
    public function __construct(
        private readonly Satellite $satellite,
        private readonly string $name,
        private readonly string $slug,
    ) {}

    public function handle(): Satellite
    {
        $this->satellite->forceFill(['name' => $this->name, 'slug' => $this->slug])->save();

        return $this->satellite;
    }
}
