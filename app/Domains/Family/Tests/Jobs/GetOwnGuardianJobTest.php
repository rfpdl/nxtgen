<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\Family\Jobs\GetOwnGuardianJob;

use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;

/*
 * The signed-in parent's own Guardian row — what `/family` opens (RequireGuardian). The address names no church (Roy
 * 2026-11-07), so it is the login's own, from its family's church; a login with none gets nothing.
 */

it('finds the login\'s own guardian, with the family and its church loaded', function (): void {
    $church = church();
    $user = User::factory()->create();
    $guardian = guardianOf(familyAt(satelliteOf($church)), $user);

    $found = (new GetOwnGuardianJob(user: $user))->handle();

    expect($found?->id)->toBe($guardian->id)
        ->and($found?->family->relationLoaded('church'))->toBeTrue()
        ->and($found?->family->church->id)->toBe($church->id);
});

it('finds nothing for a login that is no parent', function (): void {
    guardianOf(familyAt(satelliteOf(church())), User::factory()->create());

    expect((new GetOwnGuardianJob(user: User::factory()->create()))->handle())->toBeNull();
});
