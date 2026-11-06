<?php

declare(strict_types=1);

use App\Data\Models\SatelliteLink;
use App\Domains\Church\Jobs\DeleteSatelliteLinkJob;

/* Remove a Link from the poster; the others stay. */

it('removes the Link and only that one', function (): void {
    $link = SatelliteLink::factory()->create();
    $other = SatelliteLink::factory()->create();

    expect((new DeleteSatelliteLinkJob(link: $link))->handle())->toBeTrue();

    expect([SatelliteLink::query()->find($link->id), SatelliteLink::query()->find($other->id)?->id])->toBe([null, $other->id]);
});
