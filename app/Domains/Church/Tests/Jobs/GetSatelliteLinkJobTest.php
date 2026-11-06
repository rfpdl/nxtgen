<?php

declare(strict_types=1);

use App\Data\Models\SatelliteLink;
use App\Domains\Church\Jobs\GetSatelliteLinkJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;

/* A Link by id from the address — found only inside this church, with its satellite. */

it('finds a Link in this church, with its satellite', function (): void {
    $church = church();
    $satellite = satelliteOf($church, ['name' => 'CCF DXB']);
    $link = SatelliteLink::factory()->for($satellite)->create(['label' => 'WhatsApp']);

    $found = (new GetSatelliteLinkJob(church: $church, id: $link->id))->handle();

    expect([$found->label, $found->satellite->name])->toBe(['WhatsApp', 'CCF DXB']);
});

it('does not find another church\'s Link by its id', function (): void {
    $theirs = SatelliteLink::factory()->for(satelliteOf(church()))->create();

    (new GetSatelliteLinkJob(church: church(), id: $theirs->id))->handle();
})->throws(ModelNotFoundException::class);
