<?php

declare(strict_types=1);

use App\Data\Models\SatelliteLink;
use App\Domains\Church\Jobs\CreateSatelliteLinkJob;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;

/* Add a Link to a satellite's poster: last in its order. */

it('adds a Link last on the satellite\'s poster, in its church', function (): void {
    $satellite = satelliteOf(church());
    SatelliteLink::factory()->for($satellite)->create(['position' => 4]);

    $link = (new CreateSatelliteLinkJob(satellite: $satellite, label: 'Facebook group', url: 'https://facebook.com/groups/ccf'))->handle();

    expect([$link->church_id, $link->satellite_id, $link->label, $link->url, $link->position])
        ->toBe([$satellite->church_id, $satellite->id, 'Facebook group', 'https://facebook.com/groups/ccf', 5]);
});

it('puts a satellite\'s first Link first', function (): void {
    $link = (new CreateSatelliteLinkJob(satellite: satelliteOf(church()), label: 'WhatsApp', url: 'https://chat.whatsapp.com/x'))->handle();

    expect($link->position)->toBe(1);
});
