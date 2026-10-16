<?php

declare(strict_types=1);

use App\Domains\Church\Jobs\GetSatellitesJob;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;

/* This church's satellites as options, by name — never another church's. */

it('lists only this church\'s satellites, in name order', function (): void {
    $church = church();
    $house = satelliteOf($church, ['name' => 'House Church']);
    $dxb = satelliteOf($church, ['name' => 'CCF DXB']);
    satelliteOf(church(), ['name' => 'Another Church Satellite']);

    $options = (new GetSatellitesJob(church: $church))->handle();

    expect(array_map(fn ($o): array => [$o->id, $o->name], $options))
        ->toBe([[$dxb->id, 'CCF DXB'], [$house->id, 'House Church']]);
});
