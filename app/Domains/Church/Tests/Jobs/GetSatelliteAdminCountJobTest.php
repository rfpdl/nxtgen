<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\Church\Jobs\GetSatelliteAdminCountJob;
use App\Enums\RoleEnum;

use function Tests\Support\atClass;
use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\grantRole;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\staffMember;

/*
 * How many active admins a satellite has (spec item 9) — the count that stops its last admin being removed. A class
 * admin, a Lifeshaper, a revoked admin and another satellite's admin are not its admins.
 */

it('counts only the satellite\'s own active admins', function (): void {
    $church = church();
    $dubai = satelliteOf($church);
    staffMember(RoleEnum::Admin, atSatellite($dubai));
    staffMember(RoleEnum::Admin, atSatellite($dubai));
    grantRole(User::factory()->create(), RoleEnum::Admin, atSatellite($dubai), active: false);
    staffMember(RoleEnum::Lifeshaper, atSatellite($dubai));
    staffMember(RoleEnum::Admin, atClass(scheduleAt($dubai)));
    staffMember(RoleEnum::Admin, atSatellite(satelliteOf($church)));

    expect((new GetSatelliteAdminCountJob(satellite: $dubai))->handle())->toBe(2);
});

it('is zero for a satellite with no admin', function (): void {
    $church = church();
    staffMember(RoleEnum::Admin, atSatellite(satelliteOf($church)));

    expect((new GetSatelliteAdminCountJob(satellite: satelliteOf($church)))->handle())->toBe(0);
});
