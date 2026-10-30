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

it('lets admins manage classes: a church admin at every satellite, a satellite admin at their own', function (): void {
    ['church' => $church, 'htc' => $htc, 'house' => $house] = fx();
    $churchAdmin = User::factory()->create();
    grantRole($churchAdmin, RoleEnum::Admin, atChurch($church));
    $htcAdmin = User::factory()->create();
    grantRole($htcAdmin, RoleEnum::Admin, atSatellite($htc));

    $all = (new GetAccessScopeJob(user: $churchAdmin, church: $church))->handle();
    $own = (new GetAccessScopeJob(user: $htcAdmin, church: $church))->handle();

    expect([$all->canManageClasses(), $all->canManageClassesAt($htc), $all->canManageClassesAt($house)])->toBe([true, true, true])
        ->and([$own->canManageClasses(), $own->canManageClassesAt($htc), $own->canManageClassesAt($house)])->toBe([true, true, false])
        ->and($all->classSatelliteIds())->toBeNull()
        ->and($own->classSatelliteIds())->toBe([$htc->id]);
});

it('never lets a Lifeshaper manage classes, nor anyone another church\'s', function (): void {
    ['church' => $church, 'htc' => $htc] = fx();
    $lifeshaper = User::factory()->create();
    grantRole($lifeshaper, RoleEnum::Lifeshaper, atChurch($church));
    $admin = User::factory()->create();
    grantRole($admin, RoleEnum::Admin, atChurch($church));
    $elsewhere = satelliteOf(church());

    $scope = (new GetAccessScopeJob(user: $lifeshaper, church: $church))->handle();

    expect([$scope->canManageClasses(), $scope->canManageClassesAt($htc), $scope->classSatelliteIds()])->toBe([false, false, []])
        ->and((new GetAccessScopeJob(user: $admin, church: $church))->handle()->canManageClassesAt($elsewhere))->toBeFalse();
});

it('shows Attendance to admins — a church admin every satellite, a satellite admin their own — and never to a Lifeshaper', function (): void {
    ['church' => $church, 'htc' => $htc] = fx();
    $scopeOf = function (RoleEnum $role, bool $churchWide) use ($church, $htc) {
        $user = User::factory()->create();
        grantRole($user, $role, $churchWide ? atChurch($church) : atSatellite($htc));

        return (new GetAccessScopeJob(user: $user, church: $church))->handle();
    };

    $all = $scopeOf(RoleEnum::Admin, true);
    $own = $scopeOf(RoleEnum::Admin, false);
    $lifeshaper = $scopeOf(RoleEnum::Lifeshaper, true);

    expect([$all->canSeeAttendance(), $all->attendanceSatelliteIds()])->toBe([true, null])
        ->and([$own->canSeeAttendance(), $own->attendanceSatelliteIds()])->toBe([true, [$htc->id]])
        ->and([$lifeshaper->canSeeAttendance(), $lifeshaper->attendanceSatelliteIds()])->toBe([false, []]);
});

it('opens Cards to admins — a church admin every satellite\'s requests, a satellite admin their own — and never to a Lifeshaper', function (): void {
    ['church' => $church, 'htc' => $htc] = fx();
    $scopeOf = function (RoleEnum $role, bool $churchWide) use ($church, $htc) {
        $user = User::factory()->create();
        grantRole($user, $role, $churchWide ? atChurch($church) : atSatellite($htc));

        return (new GetAccessScopeJob(user: $user, church: $church))->handle();
    };

    $all = $scopeOf(RoleEnum::Admin, true);
    $own = $scopeOf(RoleEnum::Admin, false);
    $lifeshaper = $scopeOf(RoleEnum::Lifeshaper, true);

    expect([$all->canManageCards(), $all->cardSatelliteIds()])->toBe([true, null])
        ->and([$own->canManageCards(), $own->cardSatelliteIds()])->toBe([true, [$htc->id]])
        ->and([$lifeshaper->canManageCards(), $lifeshaper->cardSatelliteIds()])->toBe([false, []]);
});

it('lets anyone with a Role at a satellite check children in there; a church-wide Role anywhere', function (RoleEnum $role, string $where, bool $atHtc, bool $atHouse): void {
    ['church' => $church, 'htc' => $htc, 'house' => $house] = fx();
    $user = User::factory()->create();
    grantRole($user, $role, $where === 'church' ? atChurch($church) : atSatellite($htc));

    $scope = (new GetAccessScopeJob(user: $user, church: $church))->handle();

    expect([$scope->canCheckInAt($htc), $scope->canCheckInAt($house)])->toBe([$atHtc, $atHouse])
        ->and($scope->doorSatelliteIds())->toBe($where === 'church' ? null : [$htc->id]);
})->with([
    'church admin' => [RoleEnum::Admin, 'church', true, true],
    'church Lifeshaper' => [RoleEnum::Lifeshaper, 'church', true, true],
    'satellite admin' => [RoleEnum::Admin, 'satellite', true, false],
    'satellite Lifeshaper' => [RoleEnum::Lifeshaper, 'satellite', true, false],
]);

it('never lets anyone check in at another church\'s satellite, nor with a switched-off Role', function (): void {
    ['church' => $church, 'htc' => $htc] = fx();
    $admin = User::factory()->create();
    grantRole($admin, RoleEnum::Admin, atChurch($church));
    $revoked = User::factory()->create();
    grantRole($revoked, RoleEnum::Lifeshaper, atSatellite($htc), active: false);

    expect((new GetAccessScopeJob(user: $admin, church: $church))->handle()->canCheckInAt(satelliteOf(church())))->toBeFalse()
        ->and((new GetAccessScopeJob(user: $revoked, church: $church))->handle()->canCheckInAt($htc))->toBeFalse();
});
