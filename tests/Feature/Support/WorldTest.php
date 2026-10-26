<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\Church\Jobs\GetAccessScopeJob;
use App\Enums\RoleEnum;
use App\Enums\WeekdayEnum;

use function Tests\Support\atChurch;
use function Tests\Support\atSatellite;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\grantRole;
use function Tests\Support\guardianOf;
use function Tests\Support\otherGuardianOf;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\staffMember;

/*
 * The shared test World: every feature test builds its church, satellites, families and staff from these
 * blocks. Each block is checked here through the real access rules, so a block that quietly grants MORE than
 * asked (and would hide a refused-access check in some other test) fails here first.
 */

it('makes a church with named satellites', function (): void {
    $church = church(['slug' => 'ccf']);

    $dxb = satelliteOf($church, ['name' => 'CCF DXB']);

    expect($church->slug)->toBe('ccf')
        ->and($dxb->church_id)->toBe($church->id)
        ->and($dxb->name)->toBe('CCF DXB');
});

it('makes a satellite staff member who sees only their own satellite', function (): void {
    $church = church();
    $dxb = satelliteOf($church);
    $ours = familyAt($dxb);
    $theirs = familyAt(satelliteOf($church));

    $lifeshaper = staffMember(RoleEnum::Lifeshaper, atSatellite($dxb));
    $scope = (new GetAccessScopeJob(user: $lifeshaper, church: $church))->handle();

    expect($scope->canSeeFamily($ours))->toBeTrue()
        ->and($scope->canSeeFamily($theirs))->toBeFalse();
});

it('gives a role only in the church its scope belongs to', function (): void {
    $ccf = church();
    $other = church();

    $admin = staffMember(RoleEnum::Admin, atChurch($ccf));

    expect((new GetAccessScopeJob(user: $admin, church: $ccf))->handle()->canWorkHere())->toBeTrue()
        ->and((new GetAccessScopeJob(user: $admin, church: $other))->handle()->canWorkHere())->toBeFalse();
});

it('gives an existing user a role, or a revoked one that opens nothing', function (): void {
    $church = church();
    $admin = User::factory()->create();
    $revoked = User::factory()->create();

    grantRole($admin, RoleEnum::Admin, atChurch($church));
    grantRole($revoked, RoleEnum::Admin, atChurch($church), active: false);

    expect((new GetAccessScopeJob(user: $admin, church: $church))->handle()->canWorkHere())->toBeTrue()
        ->and((new GetAccessScopeJob(user: $revoked, church: $church))->handle()->canWorkHere())->toBeFalse();
});

it('makes a signed-in guardian the primary guardian of their family', function (): void {
    $family = familyAt(satelliteOf(church()));
    $user = User::factory()->create();

    $guardian = guardianOf($family, $user, ['first_name' => 'Janice']);

    expect($family->refresh()->primary_guardian_id)->toBe($guardian->id)
        ->and($guardian->user_id)->toBe($user->id)
        ->and($guardian->church_id)->toBe($family->church_id)
        ->and($guardian->first_name)->toBe('Janice');
});

it('puts a child on the family, in the family\'s church', function (): void {
    $family = familyAt(satelliteOf(church()));

    $child = childOf($family, ['nickname' => 'Amara']);

    expect($child->family_id)->toBe($family->id)
        ->and($child->church_id)->toBe($family->church_id)
        ->and($child->nickname)->toBe('Amara');
});

it('makes another guardian of a family, with no login, never the primary', function (): void {
    $family = familyAt(satelliteOf(church()));
    $primary = guardianOf($family, User::factory()->create());

    $lola = otherGuardianOf($family, ['first_name' => 'Lola']);

    expect($family->refresh()->primary_guardian_id)->toBe($primary->id)
        ->and([$lola->family_id, $lola->church_id, $lola->user_id, $lola->first_name])->toBe([$family->id, $family->church_id, null, 'Lola']);
});

it('makes a satellite\'s class, in the satellite\'s church, active on Saturdays unless told otherwise', function (): void {
    $satellite = satelliteOf(church());

    $class = scheduleAt($satellite, ['name' => '7PM', 'starts_at' => '19:00', 'ends_at' => '21:00']);
    $special = scheduleAt($satellite, ['name' => 'Special class', 'weekday' => null, 'is_active' => false]);

    expect([$class->church_id, $class->satellite_id, $class->name, $class->weekday, $class->is_active])
        ->toBe([$satellite->church_id, $satellite->id, '7PM', WeekdayEnum::Saturday, true])
        ->and([$class->fresh()?->startsAt(), $class->fresh()?->endsAt()])->toBe(['19:00', '21:00'])
        ->and([$special->weekday, $special->is_active])->toBe([null, false]);
});
