<?php

declare(strict_types=1);

use App\Domains\Church\Jobs\UpdateSatelliteJob;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;

/* A satellite renamed and moved keeps its registration code (spec item 7). */

it('renames and moves a satellite, and keeps its code', function (): void {
    $dubai = satelliteOf(church(), ['name' => 'CCF Dxb', 'slug' => 'dubai', 'code' => 'k7m2q9xa']);

    (new UpdateSatelliteJob(satellite: $dubai, name: 'CCF Dubai', slug: 'dubai-north'))->handle();

    expect([$dubai->fresh()?->name, $dubai->fresh()?->slug, $dubai->fresh()?->code])->toBe(['CCF Dubai', 'dubai-north', 'k7m2q9xa']);
});
