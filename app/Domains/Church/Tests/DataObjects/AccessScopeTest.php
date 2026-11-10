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

use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\grantRole;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;

/*
 * The access matrix (plan §3.2), one rule per test. AccessScope is the ONE owner of these answers;
 * Features ask it and never compare roles themselves. It is built for ONE satellite — the one in the address — so every
 * satellite list is that satellite or nothing. There is no church-wide Role (spec item 9): "an admin of everything" is
 * an admin at each satellite (DECISIONS 2026-11-08).
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

/** A new user holding `$role` at each of `$satellites` — one Role per satellite (no church-wide Role, spec item 9). */
function atBoth(RoleEnum $role, Satellite ...$satellites): User
{
    $user = User::factory()->create();
    foreach ($satellites as $satellite) {
        grantRole($user, $role, atSatellite($satellite));
    }

    return $user;
}

// DECISIONS 2026-11-08: an admin at both satellites is admin at each satellite's own address — every family and every
// edit, one satellite at a time — and Level, Moving-Up and merging are an admin's here (spec item 9).
it('gives an admin of both satellites every family and every edit, each at its own satellite\'s address', function (): void {
    ['htc' => $htc, 'house' => $house] = fx();
    $user = atBoth(RoleEnum::Admin, $htc, $house);

    $scope = scopeAt($user, $htc);

    expect($scope->canWorkHere())->toBeTrue()
        ->and($scope->visibleSatelliteIds())->toBe([$htc->id])
        ->and($scope->canEditFamily(familyAt($htc)))->toBeTrue()
        ->and($scope->canEditFamily(familyAt($house)))->toBeFalse()
        ->and(scopeAt($user, $house)->canEditFamily(familyAt($house)))->toBeTrue()
        ->and($scope->canSetLevel())->toBeTrue()
        ->and($scope->canMoveUp())->toBeTrue();
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
        // Level and Moving-Up are a satellite admin's, for this satellite (DECISIONS 2026-11-08).
        ->and([$scope->canSetLevel(), $scope->canMoveUp()])->toBe([true, true])
        ->and(scopeAt($user, $house)->canWorkHere())->toBeFalse()
        ->and([scopeAt($user, $house)->canSetLevel(), scopeAt($user, $house)->canMoveUp()])->toBe([false, false]);
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
    $atBoth = atBoth(RoleEnum::Lifeshaper, $htc, $house);

    $scope = scopeAt($user, $htc);
    // A Lifeshaper at both satellites is a Lifeshaper at each satellite's address (DECISIONS 2026-11-08).
    $wide = scopeAt($atBoth, $house);

    expect($scope->canEditFamilyDetails(familyAt($htc)))->toBeTrue()
        ->and($scope->canEditFamilyDetails(familyAt($house)))->toBeFalse()
        ->and($scope->canEditFamily(familyAt($htc)))->toBeFalse()
        ->and($wide->canEditFamilyDetails(familyAt($house)))->toBeTrue()
        ->and($wide->canEditFamily(familyAt($house)))->toBeFalse()
        ->and(scopeAt($atBoth, $htc)->canEditFamilyDetails(familyAt($house)))->toBeFalse()
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
    grantRole($user, RoleEnum::Admin, atSatellite($htc), active: false);
    grantRole($user, RoleEnum::Admin, atSatellite(satelliteOf(church())));

    $scope = scopeAt($user, $htc);

    expect($scope->canWorkHere())->toBeFalse()
        ->and($scope->visibleSatelliteIds())->toBe([])
        ->and($scope->canSeeFamily(familyAt($htc)))->toBeFalse();
});

it('never lets a family from another church through, even for an admin', function (): void {
    ['htc' => $htc] = fx();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Admin, atSatellite($htc));
    $foreign = Family::factory()->create();

    $scope = scopeAt($user, $htc);

    expect($scope->canSeeFamily($foreign))->toBeFalse()
        ->and($scope->canEditFamily($foreign))->toBeFalse();
});

/*
 * People (plan §3.2 "Grant roles" + the role-grant ceiling, spec item 9; DECISIONS 2026-11-08): an admin here gives and
 * removes any Role — Admin or Lifeshaper — at this satellite, and the Admin Role at its classes; nothing at another
 * satellite, even one they are admin of too (that is its own address); nobody else grants at all.
 */
it('lets an admin here grant Admin and Lifeshaper at this satellite and Admin at its classes', function (): void {
    ['htc' => $htc] = fx();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Admin, atSatellite($htc));
    $class = scheduleAt($htc);

    $scope = scopeAt($user, $htc);

    expect($scope->canManagePeople())->toBeTrue()
        ->and($scope->canGrant(RoleEnum::Admin, RoleScope::of($htc)))->toBeTrue()
        ->and($scope->canGrant(RoleEnum::Lifeshaper, RoleScope::of($htc)))->toBeTrue()
        ->and($scope->canGrant(RoleEnum::Admin, RoleScope::of($class)))->toBeTrue()
        // A class Role is only ever Admin (TODO #9), and its satellite must be known.
        ->and($scope->canGrant(RoleEnum::Lifeshaper, RoleScope::of($class)))->toBeFalse()
        ->and($scope->canGrant(RoleEnum::Admin, new RoleScope(ScopeTypeEnum::Schedule, $class->id)))->toBeFalse();
});

it('lets an admin here grant nothing at another satellite or its classes, even one they are admin of too', function (): void {
    ['htc' => $htc, 'house' => $house] = fx();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Admin, atSatellite($htc));
    $both = atBoth(RoleEnum::Admin, $htc, $house);
    $houseClass = scheduleAt($house);
    $foreign = satelliteOf(church());

    $scope = scopeAt($user, $htc);
    $bothHere = scopeAt($both, $htc);

    expect($scope->canGrant(RoleEnum::Lifeshaper, RoleScope::of($house)))->toBeFalse()
        ->and($scope->canGrant(RoleEnum::Admin, RoleScope::of($house)))->toBeFalse()
        ->and($scope->canGrant(RoleEnum::Admin, RoleScope::of($houseClass)))->toBeFalse()
        ->and($scope->canGrant(RoleEnum::Admin, RoleScope::of($foreign)))->toBeFalse()
        ->and($bothHere->canGrant(RoleEnum::Admin, RoleScope::of($house)))->toBeFalse()
        ->and($bothHere->canGrant(RoleEnum::Admin, RoleScope::of($houseClass)))->toBeFalse()
        ->and(scopeAt($both, $house)->canGrant(RoleEnum::Admin, RoleScope::of($house)))->toBeTrue();
});

it('does not let a Lifeshaper manage people', function (): void {
    ['htc' => $htc] = fx();
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Lifeshaper, atSatellite($htc));

    $scope = scopeAt($user, $htc);

    expect($scope->canManagePeople())->toBeFalse()
        ->and($scope->canGrant(RoleEnum::Lifeshaper, RoleScope::of($htc)))->toBeFalse()
        ->and($scope->canGrant(RoleEnum::Admin, RoleScope::of(scheduleAt($htc))))->toBeFalse();
});

/*
 * Re-sending the confirmation email (DECISIONS 2026-10-17, later still — Roy): anyone with an active staff role in
 * the church may, whichever satellite the family lives at — a Lifeshaper at the door helps a visiting family, and
 * an email changes no data. Never a family of another church, never a revoked role.
 */
it('lets anyone with a staff role in the church re-send a family\'s confirmation link', function (RoleEnum $role, string $where): void {
    ['church' => $church, 'htc' => $htc, 'house' => $house] = fx();
    if ($where === 'both') {
        $user = atBoth($role, $htc, $house);
    } else {
        $user = User::factory()->create();
        grantRole($user, $role, match ($where) {
            'home' => atSatellite($htc),
            'away' => atSatellite($house),
            'class' => new TestRoleScope($church, ScopeTypeEnum::Schedule, scheduleAt($house)->id),
            default => throw new LogicException("Unknown place: {$where}"),
        });
    }

    // From their own satellite's address, on a family of any satellite (DECISIONS 2026-11-08).
    $scope = scopeAt($user, in_array($where, ['away', 'class'], true) ? $house : $htc);

    expect($scope->canResendLink(familyAt($htc)))->toBeTrue()
        ->and($scope->canResendLink(Family::factory()->create()))->toBeFalse();
})->with([
    'admin at both satellites' => [RoleEnum::Admin, 'both'],
    'Lifeshaper at both satellites' => [RoleEnum::Lifeshaper, 'both'],
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

// DECISIONS 2026-11-08: an admin at both satellites manages each one's classes, each at that satellite's address.
it('lets admins manage classes: an admin at both satellites at each (at its address), a satellite admin at their own', function (): void {
    ['htc' => $htc, 'house' => $house] = fx();
    $churchAdmin = atBoth(RoleEnum::Admin, $htc, $house);
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
    ['htc' => $htc] = fx();
    $lifeshaper = User::factory()->create();
    grantRole($lifeshaper, RoleEnum::Lifeshaper, atSatellite($htc));
    $admin = User::factory()->create();
    grantRole($admin, RoleEnum::Admin, atSatellite($htc));
    $elsewhere = satelliteOf(church());

    $scope = scopeAt($lifeshaper, $htc);

    expect([$scope->canManageClasses(), $scope->canManageClassesAt($htc), $scope->classSatelliteIds()])->toBe([false, false, []])
        ->and(scopeAt($admin, $htc)->canManageClassesAt($elsewhere))->toBeFalse();
});

// DECISIONS 2026-11-08: an admin at both satellites sees each one's Attendance at that satellite's address.
it('shows Attendance to admins — an admin at both satellites each one, a satellite admin their own — and never to a Lifeshaper', function (): void {
    ['htc' => $htc, 'house' => $house] = fx();
    $scopeOf = function (RoleEnum $role, bool $bothSatellites) use ($htc, $house) {
        return scopeAt($bothSatellites ? atBoth($role, $htc, $house) : atBoth($role, $htc), $htc);
    };

    $all = $scopeOf(RoleEnum::Admin, true);
    $own = $scopeOf(RoleEnum::Admin, false);
    $lifeshaper = $scopeOf(RoleEnum::Lifeshaper, true);

    expect($all->canSeeAttendance())->toBeTrue()
        ->and($own->canSeeAttendance())->toBeTrue()
        ->and($lifeshaper->canSeeAttendance())->toBeFalse();
});

// DECISIONS 2026-11-08: an admin at both satellites sees each one's Card requests at that satellite's address.
it('opens Cards to admins — an admin at both satellites each one\'s requests, a satellite admin their own — and never to a Lifeshaper', function (): void {
    ['htc' => $htc, 'house' => $house] = fx();
    $scopeOf = function (RoleEnum $role, bool $bothSatellites) use ($htc, $house) {
        return scopeAt($bothSatellites ? atBoth($role, $htc, $house) : atBoth($role, $htc), $htc);
    };

    $all = $scopeOf(RoleEnum::Admin, true);
    $own = $scopeOf(RoleEnum::Admin, false);
    $lifeshaper = $scopeOf(RoleEnum::Lifeshaper, true);

    expect([$all->canManageCards(), $all->cardSatelliteIds()])->toBe([true, [$htc->id]])
        ->and([$own->canManageCards(), $own->cardSatelliteIds()])->toBe([true, [$htc->id]])
        ->and([$lifeshaper->canManageCards(), $lifeshaper->cardSatelliteIds()])->toBe([false, []]);
});

// DECISIONS 2026-11-08: the door is the address satellite's — a Role at both satellites checks in at each, at that
// satellite's own address.
it('lets anyone with a Role at a satellite check children in there; a Role at both satellites at each', function (RoleEnum $role, string $where, bool $atHtc, bool $atHouse): void {
    ['htc' => $htc, 'house' => $house] = fx();
    $user = $where === 'both' ? atBoth($role, $htc, $house) : atBoth($role, $htc);

    $scope = scopeAt($user, $htc);
    $atHouseAddress = scopeAt($user, $house);

    expect([$scope->canCheckInTo(scheduleAt($htc)), $atHouseAddress->canCheckInTo(scheduleAt($house))])->toBe([$atHtc, $atHouse])
        ->and($scope->canCheckInTo(scheduleAt($house)))->toBeFalse()
        ->and($scope->doorSatelliteIds())->toBe([$htc->id])
        ->and($atHouseAddress->doorSatelliteIds())->toBe($where === 'both' ? [$house->id] : []);
})->with([
    'admin at both satellites' => [RoleEnum::Admin, 'both', true, true],
    'Lifeshaper at both satellites' => [RoleEnum::Lifeshaper, 'both', true, true],
    'satellite admin' => [RoleEnum::Admin, 'satellite', true, false],
    'satellite Lifeshaper' => [RoleEnum::Lifeshaper, 'satellite', true, false],
]);

it('never lets anyone check in at another church\'s satellite, nor with a switched-off Role', function (): void {
    ['htc' => $htc] = fx();
    $admin = User::factory()->create();
    grantRole($admin, RoleEnum::Admin, atSatellite($htc));
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
        ->and([$scope->canSeeAttendance(), $scope->canSeeAttendanceOf($onePm), $scope->canSeeAttendanceOf($sevenPm)])->toBe([true, true, false]);
});

it('lets a class admin work at their class\'s satellite as a Lifeshaper there, and into Admin only for Classes and Attendance (TODO #9)', function (): void {
    ['scope' => $scope, 'htc' => $htc, 'house' => $house] = classAdminScope();

    expect($scope->visibleSatelliteIds())->toBe([$htc->id])
        ->and([$scope->canSeeFamily(familyAt($htc)), $scope->canSeeFamily(familyAt($house)), $scope->canEditFamily(familyAt($htc))])->toBe([true, false, false])
        ->and([$scope->canUseAdmin(), $scope->canUseFullAdmin()])->toBe([true, false])
        ->and([$scope->canManagePeople(), $scope->canManageCards(), $scope->canManageLinks(), $scope->canReviewDuplicates(), $scope->canMoveUp()])->toBe([false, false, false, false, false]);
});

// DECISIONS 2026-11-08: an admin at both satellites manages each one's Links at that satellite's address.
it('lets an admin manage the Links of the satellites they look after — an admin at both satellites each one\'s (Roy 2026-11-06)', function (): void {
    ['htc' => $htc, 'house' => $house] = fx();
    $churchAdmin = atBoth(RoleEnum::Admin, $htc, $house);
    $satelliteAdmin = User::factory()->create();
    grantRole($satelliteAdmin, RoleEnum::Admin, atSatellite($htc));
    $lifeshaper = atBoth(RoleEnum::Lifeshaper, $htc, $house);

    $wide = scopeAt($churchAdmin, $htc);
    $own = scopeAt($satelliteAdmin, $htc);
    $serves = scopeAt($lifeshaper, $htc);

    expect([$wide->canManageLinks(), $wide->linkSatelliteIds(), $wide->canManageLinksAt($htc), $wide->canManageLinksAt($house)])->toBe([true, [$htc->id], true, false])
        ->and(scopeAt($churchAdmin, $house)->canManageLinksAt($house))->toBeTrue()
        ->and([$own->canManageLinks(), $own->linkSatelliteIds(), $own->canManageLinksAt($htc), $own->canManageLinksAt($house)])->toBe([true, [$htc->id], true, false])
        ->and([$serves->canManageLinks(), $serves->canManageLinksAt($htc)])->toBe([false, false])
        ->and($wide->canManageLinksAt(satelliteOf(church())))->toBeFalse();
});
