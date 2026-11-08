<?php

declare(strict_types=1);

use App\Domains\Church\Jobs\GetSatelliteBySlugJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;

/* The satellite an address names (`/dubai/…`, Roy 2026-11-07), with its church — the church is never in the address. */

it('finds the satellite an address names, with its church', function (): void {
    $ccf = church(['name' => 'CCF']);
    satelliteOf($ccf, ['slug' => 'dubai', 'name' => 'CCF Dubai']);

    $found = (new GetSatelliteBySlugJob(slug: 'dubai'))->handle();

    expect($found->name)->toBe('CCF Dubai')
        ->and($found->relationLoaded('church'))->toBeTrue()
        ->and($found->church->name)->toBe('CCF');
});

it('finds no satellite at an address nobody has', function (): void {
    satelliteOf(church(), ['slug' => 'dubai']);

    (new GetSatelliteBySlugJob(slug: 'sharjah'))->handle();
})->throws(ModelNotFoundException::class);
