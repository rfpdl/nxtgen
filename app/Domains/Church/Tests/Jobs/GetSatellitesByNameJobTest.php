<?php

declare(strict_types=1);

use App\Domains\Church\Jobs\GetSatellitesByNameJob;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;

/* A church's satellites by name, as the registry sheet names them ("CCF DXB", "house church"). */

it('keys this church\'s satellites by their lower-cased name', function (): void {
    $church = church();
    $dxb = satelliteOf($church, ['name' => 'CCF DXB']);
    $house = satelliteOf($church, ['name' => 'House Church']);
    satelliteOf(church(), ['name' => 'Elsewhere']);

    $byName = (new GetSatellitesByNameJob(church: $church))->handle();

    expect(array_keys($byName))->toEqualCanonicalizing(['ccf dxb', 'house church'])
        ->and($byName['ccf dxb']->id)->toBe($dxb->id)
        ->and($byName['house church']->id)->toBe($house->id);
});
