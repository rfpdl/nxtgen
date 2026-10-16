<?php

declare(strict_types=1);

use App\Data\Models\Guardian;
use App\Data\Models\User;
use App\Domains\User\Jobs\GetUserIsFamilyOnlyJob;
use App\Enums\RoleEnum;

use function Tests\Support\atChurch;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\grantRole;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;

/* A church admin may change a login's email only when it is one unconfirmed family's login and nothing else. */

it('is true for an unconfirmed login used by exactly one family', function (): void {
    $user = User::factory()->unverified()->create();
    guardianOf(familyAt(satelliteOf(church())), $user);

    expect((new GetUserIsFamilyOnlyJob(user: $user))->handle())->toBeTrue();
});

it('is false once the email is confirmed', function (): void {
    $user = User::factory()->create();
    guardianOf(familyAt(satelliteOf(church())), $user);

    expect((new GetUserIsFamilyOnlyJob(user: $user))->handle())->toBeFalse();
});

it('is false for a login that also holds a role, even a revoked one', function (): void {
    $church = church();
    $user = User::factory()->unverified()->create();
    guardianOf(familyAt(satelliteOf($church)), $user);
    grantRole($user, RoleEnum::Lifeshaper, atChurch($church), active: false);

    expect((new GetUserIsFamilyOnlyJob(user: $user))->handle())->toBeFalse();
});

it('is false for the platform admin and for a login on two families', function (): void {
    $admin = User::factory()->unverified()->platformAdmin()->create();
    guardianOf(familyAt(satelliteOf(church())), $admin);

    $twoFamilies = User::factory()->unverified()->create();
    guardianOf(familyAt(satelliteOf(church())), $twoFamilies);
    $other = familyAt(satelliteOf(church()));
    Guardian::factory()->create(['family_id' => $other->id, 'church_id' => $other->church_id, 'user_id' => $twoFamilies->id]);

    expect((new GetUserIsFamilyOnlyJob(user: $admin->fresh() ?? $admin))->handle())->toBeFalse()
        ->and((new GetUserIsFamilyOnlyJob(user: $twoFamilies))->handle())->toBeFalse();
});

it('is false for a login no family uses', function (): void {
    expect((new GetUserIsFamilyOnlyJob(user: User::factory()->unverified()->create()))->handle())->toBeFalse();
});
