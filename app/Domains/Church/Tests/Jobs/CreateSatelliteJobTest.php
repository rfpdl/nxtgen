<?php

declare(strict_types=1);

use App\Data\Models\Squad;
use App\Domains\Church\Jobs\CreateSatelliteJob;
use App\Foundation\Support\SatelliteCode;

use function Tests\Support\church;

/* A new satellite: its name and address, a registration code of its own, and the chart's Squads. */

it('adds a satellite with its own code and the chart\'s Squads', function (): void {
    $church = church();

    $alAin = (new CreateSatelliteJob(church: $church, name: 'CCF Al Ain', slug: 'al-ain'))->handle();

    expect([$alAin->church_id, $alAin->name, $alAin->slug])->toBe([$church->id, 'CCF Al Ain', 'al-ain'])
        ->and(SatelliteCode::looksLikeOne($alAin->code))->toBeTrue()
        ->and(Squad::query()->where('satellite_id', $alAin->id)->count())->toBe(7);
});
