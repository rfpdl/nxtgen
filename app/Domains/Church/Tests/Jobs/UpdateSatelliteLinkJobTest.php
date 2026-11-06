<?php

declare(strict_types=1);

use App\Data\Models\SatelliteLink;
use App\Domains\Church\Jobs\UpdateSatelliteLinkJob;

/* Rename a Link or change its address; its place stays. */

it('changes the label and the address, keeping its place', function (): void {
    $link = SatelliteLink::factory()->create(['label' => 'Old', 'url' => 'https://old.example', 'position' => 3]);

    (new UpdateSatelliteLinkJob(link: $link, label: 'New', url: 'https://new.example'))->handle();

    expect([$link->fresh()?->label, $link->fresh()?->url, $link->fresh()?->position])->toBe(['New', 'https://new.example', 3]);
});
