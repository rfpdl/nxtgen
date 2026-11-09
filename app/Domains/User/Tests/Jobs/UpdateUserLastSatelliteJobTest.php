<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\User\Jobs\UpdateUserLastSatelliteJob;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;

/* The satellite a person opened last, kept on their account (DECISIONS 2026-11-08) — where they land next time. */

it('keeps the satellite on the account', function (): void {
    $user = User::factory()->create();
    $dubai = satelliteOf(church());

    (new UpdateUserLastSatelliteJob(user: $user, satellite: $dubai))->handle();

    expect($user->fresh()?->last_satellite_id)->toBe($dubai->id);
});
