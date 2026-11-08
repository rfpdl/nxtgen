<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Satellite;
use App\Foundation\Jobs\BaseJob;

/**
 * The satellite an address names (`/dubai/…`, Roy 2026-11-07), with its church — the church is never in the address,
 * so it comes from here. Not found is a 404 (the `{satellite}` route binding).
 *
 * @extends BaseJob<Satellite>
 */
final class GetSatelliteBySlugJob extends BaseJob
{
    public function __construct(private readonly string $slug) {}

    public function handle(): Satellite
    {
        return Satellite::query()->with('church')->where('slug', $this->slug)->firstOrFail();
    }
}
