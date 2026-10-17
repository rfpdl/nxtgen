<?php

declare(strict_types=1);

use App\Data\Models\Church;
use App\Data\Models\Family;
use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Domains\Church\DataObjects\RoleScope;
use App\Domains\Church\Jobs\GetAccessScopeJob;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use Tests\Support\RoleScope as TestRoleScope;

use function Tests\Support\atChurch;
use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\grantRole;
use function Tests\Support\satelliteOf;

/*
 * The access matrix (plan §3.2), one rule per test. AccessScope is the ONE owner of these answers;
 * Features ask it and never compare roles themselves.
 */

/** @return array{church: Church, htc: Satellite, house: Satellite} */
function fx(): array
{
    $church = church();

    return ['church' => $church, 'htc' => satelliteOf($church), 'house' => satelliteOf($church)];
}

it('gives a church admin every family and every edit', function (): void {
    ['church' => $church, 'htc' => $htc, 'house' => $house] = fx();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Admin, atChurch($church));

    $scope = (new GetAccessScopeJob(user: $user, church: $church))->handle();

    expect($scope->canWorkHere())->toBeTrue()
        ->and($scope->visibleSatelliteIds())->toBeNull()
        ->and($scope->canEditFamily(familyAt($htc)))->toBeTrue()
        ->and($scope->canEditFamily(familyAt($house)))->toBeTrue()
        ->and($scope->canMergeRecords())->toBeTrue()
        ->and($scope->canManageChurch())->toBeTrue();
});

it('limits a satellite admin to families of that satellite', function (): void {
    ['church' => $church, 'htc' => $htc, 'house' => $house] = fx();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Admin, atSatellite($htc));

    $scope = (new GetAccessScopeJob(user: $user, church: $church))->handle();

    expect($scope->visibleSatelliteIds())->toBe([$htc->id])
        ->and($scope->canEditFamily(familyAt($htc)))->toBeTrue()
        ->and($scope->canEditFamily(familyAt($house)))->toBeFalse()
        ->and($scope->canSeeFamily(familyAt($house)))->toBeFalse()
        ->and($scope->canMergeRecords())->toBeFalse()
        ->and($scope->canManageChurch())->toBeFalse();
});

it('lets a Lifeshaper see their satellite\'s families but not edit them', function (): void {
    ['church' => $church, 'htc' => $htc] = fx();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Lifeshaper, atSatellite($htc));

    $scope = (new GetAccessScopeJob(user: $user, church: $church))->handle();
    $family = familyAt($htc);

    expect($scope->canWorkHere())->toBeTrue()
        ->and($scope->canSeeFamily($family))->toBeTrue()
        ->and($scope->canEditFamily($family))->toBeFalse()
        ->and($scope->canRegisterFamilies())->toBeTrue();
});

it('combines several satellites', function (): void {
    ['church' => $church, 'htc' => $htc, 'house' => $house] = fx();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Lifeshaper, atSatellite($htc));
    grantRole($user, RoleEnum::Lifeshaper, atSatellite($house));

    $ids = (new GetAccessScopeJob(user: $user, church: $church))->handle()->visibleSatelliteIds();

    expect($ids)->toEqualCanonicalizing([$htc->id, $house->id]);
});

it('ignores revoked roles and roles in another church', function (): void {
    ['church' => $church, 'htc' => $htc] = fx();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Admin, atChurch($church), active: false);
    $other = church();
    grantRole($user, RoleEnum::Admin, atChurch($other));

    $scope = (new GetAccessScopeJob(user: $user, church: $church))->handle();

    expect($scope->canWorkHere())->toBeFalse()
        ->and($scope->visibleSatelliteIds())->toBe([])
        ->and($scope->canSeeFamily(familyAt($htc)))->toBeFalse();
});

it('never lets a family from another church through, even for a church admin', function (): void {
    ['church' => $church] = fx();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Admin, atChurch($church));
    $foreign = Family::factory()->create();

    $scope = (new GetAccessScopeJob(user: $user, church: $church))->handle();

    expect($scope->canSeeFamily($foreign))->toBeFalse()
        ->and($scope->canEditFamily($foreign))->toBeFalse();
});

/*
 * People (plan §3.2 "Grant roles" + the role-grant ceiling): a church admin grants anything in the church;
 * a satellite admin grants only Lifeshaper, only in their own satellite; nobody else grants at all.
 */
it('lets a church admin grant any role anywhere in the church', function (): void {
    ['church' => $church, 'htc' => $htc] = fx();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Admin, atChurch($church));

    $scope = (new GetAccessScopeJob(user: $user, church: $church))->handle();

    expect($scope->canManagePeople())->toBeTrue()
        ->and($scope->canGrant(RoleEnum::Admin, new RoleScope(ScopeTypeEnum::Church, $church->id)))->toBeTrue()
        ->and($scope->canGrant(RoleEnum::Admin, new RoleScope(ScopeTypeEnum::Satellite, $htc->id)))->toBeTrue()
        ->and($scope->canGrant(RoleEnum::Lifeshaper, new RoleScope(ScopeTypeEnum::Church, $church->id)))->toBeTrue()
        ->and($scope->canGrant(RoleEnum::Admin, new RoleScope(ScopeTypeEnum::Church, $church->id + 999)))->toBeFalse();
});

it('caps a satellite admin at Lifeshaper in their own satellite', function (): void {
    ['church' => $church, 'htc' => $htc, 'house' => $house] = fx();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Admin, atSatellite($htc));

    $scope = (new GetAccessScopeJob(user: $user, church: $church))->handle();

    expect($scope->canManagePeople())->toBeTrue()
        ->and($scope->canGrant(RoleEnum::Lifeshaper, new RoleScope(ScopeTypeEnum::Satellite, $htc->id)))->toBeTrue()
        ->and($scope->canGrant(RoleEnum::Lifeshaper, new RoleScope(ScopeTypeEnum::Satellite, $house->id)))->toBeFalse()
        ->and($scope->canGrant(RoleEnum::Admin, new RoleScope(ScopeTypeEnum::Satellite, $htc->id)))->toBeFalse()
        ->and($scope->canGrant(RoleEnum::Lifeshaper, new RoleScope(ScopeTypeEnum::Church, $church->id)))->toBeFalse();
});

it('does not let a Lifeshaper manage people', function (): void {
    ['church' => $church, 'htc' => $htc] = fx();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Lifeshaper, atChurch($church));

    $scope = (new GetAccessScopeJob(user: $user, church: $church))->handle();

    expect($scope->canManagePeople())->toBeFalse()
        ->and($scope->canGrant(RoleEnum::Lifeshaper, new RoleScope(ScopeTypeEnum::Satellite, $htc->id)))->toBeFalse();
});

/*
 * Re-sending the confirmation email (DECISIONS 2026-10-17, later still — Roy): anyone with an active staff role in
 * the church may, whichever satellite the family lives at — a Lifeshaper at the door helps a visiting family, and
 * an email changes no data. Never a family of another church, never a revoked role.
 */
it('lets anyone with a staff role in the church re-send a family\'s confirmation link', function (RoleEnum $role, string $where): void {
    ['church' => $church, 'htc' => $htc, 'house' => $house] = fx();
    $user = User::factory()->create();
    grantRole($user, $role, match ($where) {
        'church' => atChurch($church),
        'home' => atSatellite($htc),
        'away' => atSatellite($house),
        'class' => new TestRoleScope($church, ScopeTypeEnum::Schedule, 99),
        default => throw new LogicException("Unknown place: {$where}"),
    });

    $scope = (new GetAccessScopeJob(user: $user, church: $church))->handle();

    expect($scope->canResendLink(familyAt($htc)))->toBeTrue()
        ->and($scope->canResendLink(Family::factory()->create()))->toBeFalse();
})->with([
    'church admin' => [RoleEnum::Admin, 'church'],
    'church Lifeshaper' => [RoleEnum::Lifeshaper, 'church'],
    'home satellite admin' => [RoleEnum::Admin, 'home'],
    'home Lifeshaper' => [RoleEnum::Lifeshaper, 'home'],
    'other-satellite admin' => [RoleEnum::Admin, 'away'],
    'other-satellite Lifeshaper' => [RoleEnum::Lifeshaper, 'away'],
    'class admin' => [RoleEnum::Admin, 'class'],
]);

it('refuses a re-send to someone whose only role was switched off', function (): void {
    ['church' => $church, 'htc' => $htc] = fx();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Lifeshaper, atSatellite($htc), active: false);

    $scope = (new GetAccessScopeJob(user: $user, church: $church))->handle();

    expect($scope->canResendLink(familyAt($htc)))->toBeFalse();
});
