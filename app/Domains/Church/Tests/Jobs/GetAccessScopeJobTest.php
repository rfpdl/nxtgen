<?php

declare(strict_types=1);

use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Domains\Church\Jobs\GetAccessScopeJob;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;

use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\grantRole;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;

/*
 * What one person may do at ONE satellite — the one in the address — read from their ACTIVE roles in its church only.
 * Only their Roles there count — at the satellite or one of its classes; there is no church-wide Role (spec item 9;
 * DECISIONS 2026-11-08).
 */

it('collects this person\'s active roles at this satellite, by role — a Role at another satellite counting only there', function (): void {
    $church = church();
    $dxb = satelliteOf($church);
    $house = satelliteOf($church);
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Lifeshaper, atSatellite($dxb));
    grantRole($user, RoleEnum::Lifeshaper, atSatellite($house));
    grantRole($user, RoleEnum::Admin, atSatellite($dxb));

    $atDxb = (new GetAccessScopeJob(user: $user, satellite: $dxb))->handle();
    $atHouse = (new GetAccessScopeJob(user: $user, satellite: $house))->handle();

    expect($atDxb->churchId)->toBe($church->id)
        ->and($atDxb->adminSatelliteIds)->toBe([$dxb->id])
        ->and($atDxb->lifeshaperSatelliteIds)->toBe([$dxb->id])
        ->and($atDxb->adminSchedules)->toBe([])
        ->and($atHouse->adminSatelliteIds)->toBe([])
        ->and($atHouse->lifeshaperSatelliteIds)->toBe([$house->id]);
});

// No church-wide Role (spec item 9): an admin at every satellite holds the Role at each, and each address shows its own.
it('counts an admin at every satellite of the church as admin at each, one address at a time', function (): void {
    $church = church();
    $dxb = satelliteOf($church);
    $house = satelliteOf($church);
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Admin, atSatellite($dxb));
    grantRole($user, RoleEnum::Admin, atSatellite($house));

    $scope = (new GetAccessScopeJob(user: $user, satellite: $dxb))->handle();

    expect($scope->adminSatelliteIds)->toBe([$dxb->id])
        ->and($scope->lifeshaperSatelliteIds)->toBe([])
        ->and((new GetAccessScopeJob(user: $user, satellite: $house))->handle()->adminSatelliteIds)->toBe([$house->id]);
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
    $satellite = satelliteOf(church());
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Admin, atSatellite($satellite), active: false);

    $scope = (new GetAccessScopeJob(user: $user, satellite: $satellite))->handle();

    expect($scope->adminSatelliteIds)->toBe([])->and($scope->canWorkHere())->toBeFalse();
});

it('ignores roles the person holds in another church', function (): void {
    $here = church();
    $elsewhere = church();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Admin, atSatellite(satelliteOf($elsewhere)));

    $scope = (new GetAccessScopeJob(user: $user, satellite: satelliteOf($here)))->handle();

    expect($scope->adminSatelliteIds)->toBe([])
        ->and($scope->canWorkHere())->toBeFalse();
});

it('ignores other people\'s roles in the same church', function (): void {
    $satellite = satelliteOf(church());
    grantRole(User::factory()->create(), RoleEnum::Admin, atSatellite($satellite));

    $scope = (new GetAccessScopeJob(user: User::factory()->create(), satellite: $satellite))->handle();

    expect($scope->adminSatelliteIds)->toBe([])->and($scope->canWorkHere())->toBeFalse();
});

// Spec item 12, Roy 2026-11-08: the platform admin may open any satellite's Admin and Serve — as its admin there,
// though they hold no Role (so they are on no satellite's People).
it('makes the platform admin an admin at any satellite, with no Role', function (): void {
    $dubai = satelliteOf(church());
    $platform = User::factory()->create(['is_platform_admin' => true]);

    $scope = (new GetAccessScopeJob(user: $platform, satellite: $dubai))->handle();

    expect($scope->canUseFullAdmin())->toBeTrue()
        ->and($scope->adminSatelliteIds)->toBe([$dubai->id])
        ->and($scope->lifeshaperSatelliteIds)->toBe([])
        ->and(RoleAssignment::query()->where('user_id', $platform->id)->exists())->toBeFalse();
});
