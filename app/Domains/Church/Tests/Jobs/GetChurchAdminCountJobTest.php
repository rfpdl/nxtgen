<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\Church\Jobs\GetChurchAdminCountJob;
use App\Enums\RoleEnum;

use function Tests\Support\atChurch;
use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\grantRole;
use function Tests\Support\satelliteOf;
use function Tests\Support\staffMember;

/* How many active church-wide admins this church has — the count that stops the last one being removed. */

it('counts only active church-wide admins of this church', function (): void {
    $church = church();
    staffMember(RoleEnum::Admin, atChurch($church));
    staffMember(RoleEnum::Admin, atChurch($church));
    grantRole(User::factory()->create(), RoleEnum::Admin, atChurch($church), active: false);
    staffMember(RoleEnum::Admin, atSatellite(satelliteOf($church)));
    staffMember(RoleEnum::Lifeshaper, atChurch($church));
    staffMember(RoleEnum::Admin, atChurch(church()));

    expect((new GetChurchAdminCountJob(church: $church))->handle())->toBe(2);
});

it('is zero for a church with no admin', function (): void {
    $church = church();
    staffMember(RoleEnum::Admin, atChurch(church()));

    expect((new GetChurchAdminCountJob(church: $church))->handle())->toBe(0);
});
