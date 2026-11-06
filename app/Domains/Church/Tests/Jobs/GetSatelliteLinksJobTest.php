<?php

declare(strict_types=1);

use App\Data\Models\SatelliteLink;
use App\Domains\Church\Jobs\GetSatelliteLinksJob;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;

/* A satellite's Links, for its poster and the Links page: in the admin's order, every asked-for satellite keyed. */

it('gives each asked-for satellite its Links in order, and an empty list to one with none', function (): void {
    $church = church();
    $dxb = satelliteOf($church);
    $house = satelliteOf($church);
    SatelliteLink::factory()->for($dxb)->create(['label' => 'Second', 'position' => 2]);
    SatelliteLink::factory()->for($dxb)->create(['label' => 'First', 'position' => 1]);

    $links = (new GetSatelliteLinksJob(church: $church, satelliteIds: [$dxb->id, $house->id]))->handle();

    expect(array_map(fn (SatelliteLink $l): string => $l->label, $links[$dxb->id]))->toBe(['First', 'Second'])
        ->and($links[$house->id])->toBe([]);
});

it('never gives another church\'s Links, even for its satellite\'s id', function (): void {
    $theirs = satelliteOf(church());
    SatelliteLink::factory()->for($theirs)->create();

    expect((new GetSatelliteLinksJob(church: church(), satelliteIds: [$theirs->id]))->handle())->toBe([$theirs->id => []]);
});
