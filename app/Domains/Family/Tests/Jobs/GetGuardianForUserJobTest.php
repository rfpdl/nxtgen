<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\Family\Jobs\GetGuardianForUserJob;

use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;

/* The login's Guardian in one church — what RequireGuardian lets through and every parent page acts on. */

it('finds the login\'s guardian in this church, with the family and its church loaded', function (): void {
    $church = church();
    $user = User::factory()->create();
    $guardian = guardianOf(familyAt(satelliteOf($church)), $user);

    $found = (new GetGuardianForUserJob(user: $user, church: $church))->handle();

    expect($found?->id)->toBe($guardian->id)
        ->and($found?->relationLoaded('family'))->toBeTrue()
        ->and($found?->family->relationLoaded('church'))->toBeTrue()
        ->and($found?->family->church->id)->toBe($church->id);
});

it('finds nothing for a guardian of another church', function (): void {
    $user = User::factory()->create();
    guardianOf(familyAt(satelliteOf(church())), $user);

    expect((new GetGuardianForUserJob(user: $user, church: church()))->handle())->toBeNull();
});
