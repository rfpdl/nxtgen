<?php

declare(strict_types=1);

use App\Data\Models\SatelliteLink;
use App\Domains\Church\Jobs\UpdateSatelliteLinkPositionJob;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;

/* Move a Link up or down its satellite's poster: it swaps places with its neighbour; at either end nothing moves. */

/** @return list<string> the satellite's labels in order */
function linkOrder(int $satelliteId): array
{
    return array_values(SatelliteLink::query()->where('satellite_id', $satelliteId)->orderBy('position')->get()->map(fn (SatelliteLink $l): string => $l->label)->all());
}

it('swaps a Link with the one above, or below', function (): void {
    $satellite = satelliteOf(church());
    $a = SatelliteLink::factory()->for($satellite)->create(['label' => 'A', 'position' => 1]);
    $b = SatelliteLink::factory()->for($satellite)->create(['label' => 'B', 'position' => 5]);
    SatelliteLink::factory()->for($satellite)->create(['label' => 'C', 'position' => 9]);

    expect((new UpdateSatelliteLinkPositionJob(link: $b, up: true))->handle())->toBeTrue()
        ->and(linkOrder($satellite->id))->toBe(['B', 'A', 'C']);
    expect((new UpdateSatelliteLinkPositionJob(link: $a->fresh() ?? $a, up: false))->handle())->toBeTrue()
        ->and(linkOrder($satellite->id))->toBe(['B', 'C', 'A']);
});

it('moves nothing past either end, and never another satellite\'s Link', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    $first = SatelliteLink::factory()->for($satellite)->create(['label' => 'First', 'position' => 1]);
    $last = SatelliteLink::factory()->for($satellite)->create(['label' => 'Last', 'position' => 2]);
    SatelliteLink::factory()->for(satelliteOf($church))->create(['label' => 'Elsewhere', 'position' => 0]);

    expect((new UpdateSatelliteLinkPositionJob(link: $first, up: true))->handle())->toBeFalse()
        ->and((new UpdateSatelliteLinkPositionJob(link: $last, up: false))->handle())->toBeFalse()
        ->and(linkOrder($satellite->id))->toBe(['First', 'Last']);
});
