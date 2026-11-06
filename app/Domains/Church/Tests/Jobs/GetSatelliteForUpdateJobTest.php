<?php

declare(strict_types=1);

use App\Domains\Church\Jobs\GetSatelliteForUpdateJob;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;

/* The satellite's own row, locked for the caller's transaction: its Links change one at a time (Astra). */

it('reads the satellite again', function (): void {
    $satellite = satelliteOf(church(), ['name' => 'CCF DXB']);
    $satellite->forceFill(['name' => 'Renamed'])->saveQuietly();

    expect((new GetSatelliteForUpdateJob(satellite: $satellite))->handle()->name)->toBe('Renamed');
});
