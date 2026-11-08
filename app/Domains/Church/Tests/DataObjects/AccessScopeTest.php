<?php

declare(strict_types=1);

use App\Data\Models\Church;
use App\Data\Models\Family;
use App\Data\Models\RoleAssignment;
use App\Data\Models\Satellite;
use App\Data\Models\Schedule;
use App\Data\Models\User;
use App\Domains\Church\DataObjects\AccessScope;
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
use function Tests\Support\scheduleAt;

/*
 * The access matrix (plan §3.2), one rule per test. AccessScope is the ONE owner of these answers;
 * Features ask it and never compare roles themselves. It is built for ONE satellite — the one in the address — so every
 * satellite list is that satellite or nothing, and a church-wide Role counts as the same Role there
 * (DECISIONS 2026-11-08).
 */

function scopeAt(User $user, Satellite $satellite): AccessScope
{
    return (new GetAccessScopeJob(user: $user, satellite: $satellite))->handle();
}

/** @return array{church: Church, htc: Satellite, house: Satellite} */
function fx(): array
{
    $church = church();

    return ['church' => $church, 'htc' => satelliteOf($church), 'house' => satelliteOf($church)];
}

// DECISIONS 2026-11-08: a church admin is admin at each satellite's own address — every family and every edit, one
// satellite at a time — and keeps the church-wide acts.
it('gives a church admin every family and every edit, each at its own satellite\'s address', function (): void {
    ['church' => $church, 'htc' => $htc, 'house' => $house] = fx();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Admin, atChurch($church));

    $scope = scopeAt($user, $htc);

    expect($scope->canWorkHere())->toBeTrue()
        ->and($scope->visibleSatelliteIds())->toBe([$htc->id])
        ->and($scope->canEditFamily(familyAt($htc)))->toBeTrue()
        ->and($scope->canEditFamily(familyAt($house)))->toBeFalse()
        ->and(scopeAt($user, $house)->canEditFamily(familyAt($house)))->toBeTrue()
        ->and($scope->canMergeRecords())->toBeTrue()
        ->and($scope->canManageChurch())->toBeTrue();
});

it('limits a satellite admin to families of that satellite', function (): void {
    ['church' => $church, 'htc' => $htc, 'house' => $house] = fx();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Admin, atSatellite($htc));

    $scope = scopeAt($user, $htc);

    expect($scope->visibleSatelliteIds())->toBe([$htc->id])
        ->and($scope->canEditFamily(familyAt($htc)))->toBeTrue()
        ->and($scope->canEditFamily(familyAt($house)))->toBeFalse()
        ->and($scope->canSeeFamily(familyAt($house)))->toBeFalse()
        ->and($scope->canMergeRecords())->toBeFalse()
        ->and($scope->canManageChurch())->toBeFalse()
        ->and(scopeAt($user, $house)->canWorkHere())->toBeFalse();
});

it('lets a Lifeshaper see their satellite\'s families but not edit them', function (): void {
    ['church' => $church, 'htc' => $htc] = fx();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Lifeshaper, atSatellite($htc));

    $scope = scopeAt($user, $htc);
    $family = familyAt($htc);

    expect($scope->canWorkHere())->toBeTrue()
        ->and($scope->canSeeFamily($family))->toBeTrue()
        ->and($scope->canEditFamily($family))->toBeFalse()
        ->and($scope->canRegisterFamilies())->toBeTrue();
});

it('lets a Lifeshaper edit the details of the families they can open — their satellites\' — and no admin edit (Roy 2026-11-06)', function (): void {
    ['church' => $church, 'htc' => $htc, 'house' => $house] = fx();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Lifeshaper, atSatellite($htc));
    $churchWide = User::factory()->create();
    grantRole($churchWide, RoleEnum::Lifeshaper, atChurch($church));

    $scope = scopeAt($user, $htc);
    // A church Lifeshaper is a Lifeshaper at each satellite's address (DECISIONS 2026-11-08).
    $wide = scopeAt($churchWide, $house);

    expect($scope->canEditFamilyDetails(familyAt($htc)))->toBeTrue()
        ->and($scope->canEditFamilyDetails(familyAt($house)))->toBeFalse()
        ->and($scope->canEditFamily(familyAt($htc)))->toBeFalse()
        ->and($wide->canEditFamilyDetails(familyAt($house)))->toBeTrue()
        ->and($wide->canEditFamily(familyAt($house)))->toBeFalse()
        ->and(scopeAt($churchWide, $htc)->canEditFamilyDetails(familyAt($house)))->toBeFalse()
        ->and($scope->canEditFamilyDetails(familyAt(satelliteOf(church()))))->toBeFalse();
});

it('gives an admin the details edit wherever they may edit the family', function (): void {
    ['church' => $church, 'htc' => $htc, 'house' => $house] = fx();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Admin, atSatellite($htc));

    $scope = scopeAt($user, $htc);

    expect($scope->canEditFamilyDetails(familyAt($htc)))->toBeTrue()
        ->and($scope->canEditFamilyDetails(familyAt($house)))->toBeFalse();
});

// DECISIONS 2026-11-08: Roles at several satellites no longer combine on one page — each satellite's address shows
// that satellite only.
it('keeps several satellites apart, each at its own address', function (): void {
    ['church' => $church, 'htc' => $htc, 'house' => $house] = fx();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Lifeshaper, atSatellite($htc));
    grantRole($user, RoleEnum::Lifeshaper, atSatellite($house));

    expect(scopeAt($user, $htc)->visibleSatelliteIds())->toBe([$htc->id])
        ->and(scopeAt($user, $house)->visibleSatelliteIds())->toBe([$house->id]);
});

it('ignores revoked roles and roles in another church', function (): void {
    ['church' => $church, 'htc' => $htc] = fx();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Admin, atChurch($church), active: false);
    $other = church();
    grantRole($user, RoleEnum::Admin, atChurch($other));

    $scope = scopeAt($user, $htc);

    expect($scope->canWorkHere())->toBeFalse()
        ->and($scope->visibleSatelliteIds())->toBe([])
        ->and($scope->canSeeFamily(familyAt($htc)))->toBeFalse();
});

it('never lets a family from another church through, even for a church admin', function (): void {
    ['church' => $church, 'htc' => $htc] = fx();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Admin, atChurch($church));
    $foreign = Family::factory()->create();

    $scope = scopeAt($user, $htc);

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

    $scope = scopeAt($user, $htc);

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

    $scope = scopeAt($user, $htc);

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

    $scope = scopeAt($user, $htc);

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
        'class' => new TestRoleScope($church, ScopeTypeEnum::Schedule, scheduleAt($house)->id),
        default => throw new LogicException("Unknown place: {$where}"),
    });

    // From their own satellite's address, on a family of any satellite (DECISIONS 2026-11-08).
    $scope = scopeAt($user, in_array($where, ['away', 'class'], true) ? $house : $htc);

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

    $scope = scopeAt($user, $htc);

    expect($scope->canResendLink(familyAt($htc)))->toBeFalse();
});

// DECISIONS 2026-11-08: a church admin manages every satellite's classes, each at that satellite's address.
it('lets admins manage classes: a church admin at every satellite (at its address), a satellite admin at their own', function (): void {
    ['church' => $church, 'htc' => $htc, 'house' => $house] = fx();
    $churchAdmin = User::factory()->create();
    grantRole($churchAdmin, RoleEnum::Admin, atChurch($church));
    $htcAdmin = User::factory()->create();
    grantRole($htcAdmin, RoleEnum::Admin, atSatellite($htc));

    $all = scopeAt($churchAdmin, $htc);
    $own = scopeAt($htcAdmin, $htc);

    expect([$all->canManageClasses(), $all->canManageClassesAt($htc), $all->canManageClassesAt($house)])->toBe([true, true, false])
        ->and(scopeAt($churchAdmin, $house)->canManageClassesAt($house))->toBeTrue()
        ->and([$own->canManageClasses(), $own->canManageClassesAt($htc), $own->canManageClassesAt($house)])->toBe([true, true, false])
        ->and(scopeAt($htcAdmin, $house)->canManageClasses())->toBeFalse()
        ->and($all->classSatelliteIds())->toBe([$htc->id])
        ->and($own->classSatelliteIds())->toBe([$htc->id]);
});

it('never lets a Lifeshaper manage classes, nor anyone another church\'s', function (): void {
    ['church' => $church, 'htc' => $htc] = fx();
    $lifeshaper = User::factory()->create();
    grantRole($lifeshaper, RoleEnum::Lifeshaper, atChurch($church));
    $admin = User::factory()->create();
    grantRole($admin, RoleEnum::Admin, atChurch($church));
    $elsewhere = satelliteOf(church());

    $scope = scopeAt($lifeshaper, $htc);

    expect([$scope->canManageClasses(), $scope->canManageClassesAt($htc), $scope->classSatelliteIds()])->toBe([false, false, []])
        ->and(scopeAt($admin, $htc)->canManageClassesAt($elsewhere))->toBeFalse();
});

// DECISIONS 2026-11-08: a church admin sees each satellite's Attendance at that satellite's address.
it('shows Attendance to admins — a church admin every satellite, a satellite admin their own — and never to a Lifeshaper', function (): void {
    ['church' => $church, 'htc' => $htc] = fx();
    $scopeOf = function (RoleEnum $role, bool $churchWide) use ($church, $htc) {
        $user = User::factory()->create();
        grantRole($user, $role, $churchWide ? atChurch($church) : atSatellite($htc));

        return scopeAt($user, $htc);
    };

    $all = $scopeOf(RoleEnum::Admin, true);
    $own = $scopeOf(RoleEnum::Admin, false);
    $lifeshaper = $scopeOf(RoleEnum::Lifeshaper, true);

    expect([$all->canSeeAttendance(), $all->attendanceSatelliteIds()])->toBe([true, [$htc->id]])
        ->and([$own->canSeeAttendance(), $own->attendanceSatelliteIds()])->toBe([true, [$htc->id]])
        ->and([$lifeshaper->canSeeAttendance(), $lifeshaper->attendanceSatelliteIds()])->toBe([false, []]);
});

// DECISIONS 2026-11-08: a church admin sees each satellite's Card requests at that satellite's address.
it('opens Cards to admins — a church admin every satellite\'s requests, a satellite admin their own — and never to a Lifeshaper', function (): void {
    ['church' => $church, 'htc' => $htc] = fx();
    $scopeOf = function (RoleEnum $role, bool $churchWide) use ($church, $htc) {
        $user = User::factory()->create();
        grantRole($user, $role, $churchWide ? atChurch($church) : atSatellite($htc));

        return scopeAt($user, $htc);
    };

    $all = $scopeOf(RoleEnum::Admin, true);
    $own = $scopeOf(RoleEnum::Admin, false);
    $lifeshaper = $scopeOf(RoleEnum::Lifeshaper, true);

    expect([$all->canManageCards(), $all->cardSatelliteIds()])->toBe([true, [$htc->id]])
        ->and([$own->canManageCards(), $own->cardSatelliteIds()])->toBe([true, [$htc->id]])
        ->and([$lifeshaper->canManageCards(), $lifeshaper->cardSatelliteIds()])->toBe([false, []]);
});

// DECISIONS 2026-11-08: the door is the address satellite's — a church-wide Role checks in anywhere, at that
// satellite's own address.
it('lets anyone with a Role at a satellite check children in there; a church-wide Role anywhere', function (RoleEnum $role, string $where, bool $atHtc, bool $atHouse): void {
    ['church' => $church, 'htc' => $htc, 'house' => $house] = fx();
    $user = User::factory()->create();
    grantRole($user, $role, $where === 'church' ? atChurch($church) : atSatellite($htc));

    $scope = scopeAt($user, $htc);
    $atHouseAddress = scopeAt($user, $house);

    expect([$scope->canCheckInTo(scheduleAt($htc)), $atHouseAddress->canCheckInTo(scheduleAt($house))])->toBe([$atHtc, $atHouse])
        ->and($scope->canCheckInTo(scheduleAt($house)))->toBeFalse()
        ->and($scope->doorSatelliteIds())->toBe([$htc->id])
        ->and($atHouseAddress->doorSatelliteIds())->toBe($where === 'church' ? [$house->id] : []);
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

    expect(scopeAt($admin, $htc)->canCheckInTo(scheduleAt(satelliteOf(church()))))->toBeFalse()
        ->and(scopeAt($revoked, $htc)->canCheckInTo(scheduleAt($htc)))->toBeFalse();
});

/**
 * A class admin of 1PM at HTC (TODO #9), and the HTC class they are not admin of.
 *
 * @return array{scope: AccessScope, htc: Satellite, house: Satellite, onePm: Schedule, sevenPm: Schedule}
 */
function classAdminScope(): array
{
    ['church' => $church, 'htc' => $htc, 'house' => $house] = fx();
    $onePm = scheduleAt($htc, ['name' => '1PM']);
    $sevenPm = scheduleAt($htc, ['name' => '7PM']);
    $user = User::factory()->create();
    RoleAssignment::query()->create(['church_id' => $church->id, 'user_id' => $user->id, 'role' => RoleEnum::Admin, 'scope_type' => ScopeTypeEnum::Schedule, 'scope_id' => $onePm->id, 'is_active' => true]);

    return ['scope' => scopeAt($user, $htc), 'htc' => $htc, 'house' => $house, 'onePm' => $onePm, 'sevenPm' => $sevenPm];
}

it('gives a class admin their class — its door, its Attendance, editing it — and nothing of another class (TODO #9)', function (): void {
    ['scope' => $scope, 'htc' => $htc, 'onePm' => $onePm, 'sevenPm' => $sevenPm] = classAdminScope();

    expect([$scope->canCheckInTo($onePm), $scope->canCheckInTo($sevenPm)])->toBe([true, false])
        ->and($scope->doorSatelliteIds())->toBe([])
        ->and($scope->classAdminScheduleIds())->toBe([$onePm->id])
        ->and([$scope->canManageClasses(), $scope->canManageSchedule($onePm), $scope->canManageSchedule($sevenPm), $scope->canManageClassesAt($htc)])->toBe([true, true, false, false])
        ->and([$scope->canSeeAttendance(), $scope->canSeeAttendanceOf($onePm), $scope->canSeeAttendanceOf($sevenPm), $scope->attendanceSatelliteIds()])->toBe([true, true, false, [$htc->id]]);
});

it('lets a class admin work at their class\'s satellite as a Lifeshaper there, and into Admin only for Classes and Attendance (TODO #9)', function (): void {
    ['scope' => $scope, 'htc' => $htc, 'house' => $house] = classAdminScope();

    expect($scope->visibleSatelliteIds())->toBe([$htc->id])
        ->and([$scope->canSeeFamily(familyAt($htc)), $scope->canSeeFamily(familyAt($house)), $scope->canEditFamily(familyAt($htc))])->toBe([true, false, false])
        ->and([$scope->canUseAdmin(), $scope->canUseFullAdmin()])->toBe([true, false])
        ->and([$scope->canManagePeople(), $scope->canManageCards(), $scope->canManageLinks(), $scope->canReviewDuplicates(), $scope->canMoveUp()])->toBe([false, false, false, false, false]);
});

// DECISIONS 2026-11-08: a church admin manages each satellite's Links at that satellite's address.
it('lets an admin manage the Links of the satellites they look after — a church admin every satellite\'s (Roy 2026-11-06)', function (): void {
    ['church' => $church, 'htc' => $htc, 'house' => $house] = fx();
    $churchAdmin = User::factory()->create();
    grantRole($churchAdmin, RoleEnum::Admin, atChurch($church));
    $satelliteAdmin = User::factory()->create();
    grantRole($satelliteAdmin, RoleEnum::Admin, atSatellite($htc));
    $lifeshaper = User::factory()->create();
    grantRole($lifeshaper, RoleEnum::Lifeshaper, atChurch($church));

    $wide = scopeAt($churchAdmin, $htc);
    $own = scopeAt($satelliteAdmin, $htc);
    $serves = scopeAt($lifeshaper, $htc);

    expect([$wide->canManageLinks(), $wide->linkSatelliteIds(), $wide->canManageLinksAt($htc), $wide->canManageLinksAt($house)])->toBe([true, [$htc->id], true, false])
        ->and(scopeAt($churchAdmin, $house)->canManageLinksAt($house))->toBeTrue()
        ->and([$own->canManageLinks(), $own->linkSatelliteIds(), $own->canManageLinksAt($htc), $own->canManageLinksAt($house)])->toBe([true, [$htc->id], true, false])
        ->and([$serves->canManageLinks(), $serves->canManageLinksAt($htc)])->toBe([false, false])
        ->and($wide->canManageLinksAt(satelliteOf(church())))->toBeFalse();
});
