<?php

declare(strict_types=1);

use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Domains\Church\Jobs\GetAccessScopeJob;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;

use function Tests\Support\atChurch;
use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\grantRole;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;

/*
 * What one person may do at ONE satellite — the one in the address — read from their ACTIVE roles in its church only.
 * Only their Roles there count; a church-wide Role counts as the same Role at this satellite (DECISIONS 2026-11-08).
 */

it('collects this person\'s active roles at this satellite, by role — a church-wide Role counting as the same Role here', function (): void {
    $church = church();
    $dxb = satelliteOf($church);
    $house = satelliteOf($church);
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Lifeshaper, atChurch($church));
    grantRole($user, RoleEnum::Admin, atSatellite($dxb));

    $atDxb = (new GetAccessScopeJob(user: $user, satellite: $dxb))->handle();
    $atHouse = (new GetAccessScopeJob(user: $user, satellite: $house))->handle();

    expect($atDxb->churchId)->toBe($church->id)
        ->and($atDxb->isChurchAdmin)->toBeFalse()
        ->and($atDxb->adminSatelliteIds)->toBe([$dxb->id])
        ->and($atDxb->lifeshaperSatelliteIds)->toBe([$dxb->id])
        ->and($atDxb->adminSchedules)->toBe([])
        ->and($atHouse->adminSatelliteIds)->toBe([])
        ->and($atHouse->lifeshaperSatelliteIds)->toBe([$house->id]);
});

it('counts a church admin as admin at every satellite of the church, keeping the church-wide powers', function (): void {
    $church = church();
    $dxb = satelliteOf($church);
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Admin, atChurch($church));

    $scope = (new GetAccessScopeJob(user: $user, satellite: $dxb))->handle();

    expect($scope->isChurchAdmin)->toBeTrue()
        ->and($scope->adminSatelliteIds)->toBe([$dxb->id])
        ->and($scope->lifeshaperSatelliteIds)->toBe([]);
});

it('gives nothing at a satellite the person has no Role at', function (): void {
    $church = church();
    $dxb = satelliteOf($church);
    $house = satelliteOf($church);
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Admin, atSatellite($dxb));

    $scope = (new GetAccessScopeJob(user: $user, satellite: $house))->handle();

    expect($scope->adminSatelliteIds)->toBe([])
        ->and($scope->lifeshaperSatelliteIds)->toBe([])
        ->and($scope->canWorkHere())->toBeFalse();
});

it('reads a class admin\'s classes at this satellite — this church\'s, still active (TODO #9)', function (): void {
    $church = church();
    $dxb = satelliteOf($church);
    $house = satelliteOf($church);
    $onePm = scheduleAt($dxb);
    $retired = scheduleAt($dxb, ['is_active' => false]);
    $atHouse = scheduleAt($house);
    $theirs = scheduleAt(satelliteOf(church()));
    $user = User::factory()->create();
    foreach ([$onePm, $retired, $atHouse, $theirs] as $class) {
        RoleAssignment::query()->create(['church_id' => $church->id, 'user_id' => $user->id, 'role' => RoleEnum::Admin, 'scope_type' => ScopeTypeEnum::Schedule, 'scope_id' => $class->id, 'is_active' => true]);
    }

    $scope = (new GetAccessScopeJob(user: $user, satellite: $dxb))->handle();

    expect($scope->adminSchedules)->toBe([$onePm->id => $dxb->id])
        ->and($scope->canWorkHere())->toBeTrue();
});

it('ignores a revoked role', function (): void {
    $church = church();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Admin, atChurch($church), active: false);

    $scope = (new GetAccessScopeJob(user: $user, satellite: satelliteOf($church)))->handle();

    expect($scope->isChurchAdmin)->toBeFalse()->and($scope->canWorkHere())->toBeFalse();
});

it('ignores roles the person holds in another church', function (): void {
    $here = church();
    $elsewhere = church();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Admin, atChurch($elsewhere));
    grantRole($user, RoleEnum::Admin, atSatellite(satelliteOf($elsewhere)));

    $scope = (new GetAccessScopeJob(user: $user, satellite: satelliteOf($here)))->handle();

    expect($scope->isChurchAdmin)->toBeFalse()
        ->and($scope->adminSatelliteIds)->toBe([])
        ->and($scope->canWorkHere())->toBeFalse();
});

it('ignores other people\'s roles in the same church', function (): void {
    $church = church();
    grantRole(User::factory()->create(), RoleEnum::Admin, atChurch($church));

    $scope = (new GetAccessScopeJob(user: User::factory()->create(), satellite: satelliteOf($church)))->handle();

    expect($scope->isChurchAdmin)->toBeFalse();
});
