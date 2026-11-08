<?php

declare(strict_types=1);

use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Domains\Church\Jobs\GetStaffRolesJob;
use App\Enums\RoleEnum;

use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\classAdminOf;
use function Tests\Support\grantRole;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\staffMember;

/* The People page's rows: the active roles at the given satellites and their classes (spec item 9: no church-wide list). */

it('lists every active role at the given satellite, with its person', function (): void {
    $church = church();
    $dxb = satelliteOf($church);
    $admin = staffMember(RoleEnum::Admin, atSatellite($dxb));
    $lifeshaper = staffMember(RoleEnum::Lifeshaper, atSatellite($dxb));
    grantRole(User::factory()->create(), RoleEnum::Lifeshaper, atSatellite($dxb), active: false);
    staffMember(RoleEnum::Admin, atSatellite(satelliteOf(church())));

    $roles = (new GetStaffRolesJob(church: $church, satelliteIds: [$dxb->id]))->handle();

    expect($roles->map(fn (RoleAssignment $r): int => $r->user_id)->all())->toBe([$admin->id, $lifeshaper->id])
        ->and($roles->every(fn (RoleAssignment $r): bool => $r->relationLoaded('user')))->toBeTrue();
});

it('lists only roles held in the given satellites, leaving out another satellite\'s', function (): void {
    $church = church();
    $dxb = satelliteOf($church);
    $house = satelliteOf($church);
    staffMember(RoleEnum::Admin, atSatellite($house));
    $mine = staffMember(RoleEnum::Lifeshaper, atSatellite($dxb));
    staffMember(RoleEnum::Lifeshaper, atSatellite($house));

    $roles = (new GetStaffRolesJob(church: $church, satelliteIds: [$dxb->id]))->handle();

    expect($roles->map(fn (RoleAssignment $r): int => $r->user_id)->all())->toBe([$mine->id]);
});

it('does not list another church\'s roles even when given its satellite id', function (): void {
    $theirs = satelliteOf(church());
    staffMember(RoleEnum::Lifeshaper, atSatellite($theirs));

    expect((new GetStaffRolesJob(church: church(), satelliteIds: [$theirs->id]))->handle())->toHaveCount(0);
});

it('lists the class admins of the given satellites\' classes too, and not another satellite\'s (TODO #9)', function (): void {
    $church = church();
    $dxb = satelliteOf($church);
    $house = satelliteOf($church);
    $mine = classAdminOf(scheduleAt($dxb));
    classAdminOf(scheduleAt($house));

    $roles = (new GetStaffRolesJob(church: $church, satelliteIds: [$dxb->id]))->handle();

    expect($roles->pluck('user_id')->all())->toBe([$mine->id]);
});
