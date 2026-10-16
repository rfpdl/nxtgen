<?php

declare(strict_types=1);

use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Domains\Church\Jobs\GetStaffRolesJob;
use App\Enums\RoleEnum;

use function Tests\Support\atChurch;
use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\grantRole;
use function Tests\Support\satelliteOf;
use function Tests\Support\staffMember;

/* The People page's rows: this church's active roles — all of them, or only those in the given satellites. */

it('lists every active role in this church, with its person, when no satellites are given', function (): void {
    $church = church();
    $dxb = satelliteOf($church);
    $admin = staffMember(RoleEnum::Admin, atChurch($church));
    $lifeshaper = staffMember(RoleEnum::Lifeshaper, atSatellite($dxb));
    grantRole(User::factory()->create(), RoleEnum::Lifeshaper, atSatellite($dxb), active: false);
    staffMember(RoleEnum::Admin, atChurch(church()));

    $roles = (new GetStaffRolesJob(church: $church, satelliteIds: null))->handle();

    expect($roles->map(fn (RoleAssignment $r): int => $r->user_id)->all())->toBe([$admin->id, $lifeshaper->id])
        ->and($roles->every(fn (RoleAssignment $r): bool => $r->relationLoaded('user')))->toBeTrue();
});

it('lists only roles held in the given satellites, leaving out church-wide roles', function (): void {
    $church = church();
    $dxb = satelliteOf($church);
    $house = satelliteOf($church);
    staffMember(RoleEnum::Admin, atChurch($church));
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
