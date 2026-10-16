<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\Church\Jobs\GetAccessScopeJob;
use App\Enums\RoleEnum;

use function Tests\Support\atChurch;
use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\grantRole;
use function Tests\Support\satelliteOf;

/* What one person may do in one church, read from their ACTIVE roles in that church only. */

it('collects this person\'s active roles in this church, by role and scope', function (): void {
    $church = church();
    $dxb = satelliteOf($church);
    $house = satelliteOf($church);
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Lifeshaper, atChurch($church));
    grantRole($user, RoleEnum::Admin, atSatellite($dxb));
    grantRole($user, RoleEnum::Lifeshaper, atSatellite($house));

    $scope = (new GetAccessScopeJob(user: $user, church: $church))->handle();

    expect($scope->churchId)->toBe($church->id)
        ->and($scope->isChurchAdmin)->toBeFalse()
        ->and($scope->isChurchLifeshaper)->toBeTrue()
        ->and($scope->adminSatelliteIds)->toBe([$dxb->id])
        ->and($scope->lifeshaperSatelliteIds)->toBe([$house->id])
        ->and($scope->adminScheduleIds)->toBe([]);
});

it('ignores a revoked role', function (): void {
    $church = church();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Admin, atChurch($church), active: false);

    $scope = (new GetAccessScopeJob(user: $user, church: $church))->handle();

    expect($scope->isChurchAdmin)->toBeFalse()->and($scope->canWorkHere())->toBeFalse();
});

it('ignores roles the person holds in another church', function (): void {
    $here = church();
    $elsewhere = church();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Admin, atChurch($elsewhere));
    grantRole($user, RoleEnum::Admin, atSatellite(satelliteOf($elsewhere)));

    $scope = (new GetAccessScopeJob(user: $user, church: $here))->handle();

    expect($scope->isChurchAdmin)->toBeFalse()
        ->and($scope->adminSatelliteIds)->toBe([])
        ->and($scope->canWorkHere())->toBeFalse();
});

it('ignores other people\'s roles in the same church', function (): void {
    $church = church();
    grantRole(User::factory()->create(), RoleEnum::Admin, atChurch($church));

    $scope = (new GetAccessScopeJob(user: User::factory()->create(), church: $church))->handle();

    expect($scope->isChurchAdmin)->toBeFalse();
});
