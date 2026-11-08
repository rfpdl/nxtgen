<?php

declare(strict_types=1);

use App\Domains\Church\Jobs\GetSatelliteByCodeJob;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;

/*
 * A satellite's registration code (spec item 7, Roy 2026-11-08): 8 letters and digits that never change, carried by its
 * printed QR poster — so the code finds its satellite whatever its address is now.
 */

it('finds the satellite a code belongs to, with its church', function (): void {
    $dubai = satelliteOf(church(['name' => 'CCF']), ['slug' => 'dubai', 'code' => 'k7m2q9xa']);

    $found = (new GetSatelliteByCodeJob(code: 'k7m2q9xa'))->handle();

    expect($found?->id)->toBe($dubai->id)
        ->and($found?->relationLoaded('church'))->toBeTrue();
});

it('still finds it after the satellite\'s address changes', function (): void {
    $dubai = satelliteOf(church(), ['slug' => 'dubai', 'code' => 'k7m2q9xa']);
    $dubai->forceFill(['slug' => 'dubai-north'])->save();

    expect((new GetSatelliteByCodeJob(code: 'k7m2q9xa'))->handle()?->slug)->toBe('dubai-north');
});

it('finds nothing for a code nobody has, or for an address', function (string $code): void {
    satelliteOf(church(), ['slug' => 'dubai', 'code' => 'k7m2q9xa']);

    expect((new GetSatelliteByCodeJob(code: $code))->handle())->toBeNull();
})->with(['unknown' => 'zzzzzzzz', 'an address' => 'dubai']);
