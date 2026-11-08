<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Satellite;
use App\Foundation\Jobs\BaseJob;

/**
 * The satellite a registration code belongs to (spec item 7), with its church — the code on a printed QR poster, which
 * never changes, so it finds the satellite whatever its address is now. Null for any other text.
 *
 * @extends BaseJob<Satellite|null>
 */
final class GetSatelliteByCodeJob extends BaseJob
{
    public function __construct(private readonly string $code) {}

    public function handle(): ?Satellite
    {
        return Satellite::query()->with('church')->where('code', $this->code)->first();
    }
}
