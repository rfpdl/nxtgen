<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\Church\Jobs\GetSatelliteAdminCountsJob;
use App\Enums\RoleEnum;

use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\grantRole;
use function Tests\Support\satelliteOf;
use function Tests\Support\staffMember;

/* How many active admins each satellite of this church has — a satellite with none is missing from the answer. */

it('counts each satellite\'s active admins, and nothing else', function (): void {
    $church = church();
    $dubai = satelliteOf($church);
    $sharjah = satelliteOf($church);
    satelliteOf($church);
    staffMember(RoleEnum::Admin, atSatellite($dubai));
    staffMember(RoleEnum::Admin, atSatellite($dubai));
    staffMember(RoleEnum::Admin, atSatellite($sharjah));
    staffMember(RoleEnum::Lifeshaper, atSatellite($sharjah));
    grantRole(User::factory()->create(), RoleEnum::Admin, atSatellite($sharjah), active: false);
    staffMember(RoleEnum::Admin, atSatellite(satelliteOf(church())));

    expect((new GetSatelliteAdminCountsJob(church: $church))->handle())->toBe([$dubai->id => 2, $sharjah->id => 1]);
});
