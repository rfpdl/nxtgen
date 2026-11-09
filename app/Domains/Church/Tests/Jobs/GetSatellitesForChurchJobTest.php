<?php

declare(strict_types=1);

use App\Data\Models\Satellite;
use App\Domains\Church\Jobs\GetSatellitesForChurchJob;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;

/* This church's satellites, in the order added — never another church's. */

it('lists this church\'s satellites in the order added', function (): void {
    $church = church();
    $first = satelliteOf($church, ['name' => 'Zeta']);
    $second = satelliteOf($church, ['name' => 'Alpha']);
    satelliteOf(church());

    expect(array_map(fn (Satellite $s): int => $s->id, (new GetSatellitesForChurchJob(church: $church))->handle()))->toBe([$first->id, $second->id]);
});
