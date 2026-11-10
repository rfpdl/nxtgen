<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\User\Jobs\GetUserIsStaffJob;
use App\Enums\RoleEnum;

use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\grantRole;
use function Tests\Support\helperAt;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;
use function Tests\Support\staffMember;

/* Staff = an active role in any church, or the platform admin. A revoked role does not count. */

it('is true for an active role in any church and for the platform admin', function (): void {
    $lifeshaper = staffMember(RoleEnum::Lifeshaper, atSatellite(satelliteOf(church())));

    expect((new GetUserIsStaffJob(user: $lifeshaper))->handle())->toBeTrue()
        ->and((new GetUserIsStaffJob(user: User::factory()->platformAdmin()->create()))->handle())->toBeTrue();
});

it('is false for a login with no role, or only a revoked one', function (): void {
    $revoked = User::factory()->create();
    grantRole($revoked, RoleEnum::Admin, atSatellite(satelliteOf(church())), active: false);

    expect((new GetUserIsStaffJob(user: User::factory()->create()))->handle())->toBeFalse()
        ->and((new GetUserIsStaffJob(user: $revoked))->handle())->toBeFalse();
});

it('is false for a helper for the day — their one day is no reason to keep a password (review, issue 14)', function (): void {
    $helper = helperAt(sessionOf(scheduleAt(satelliteOf(church()))));

    expect((new GetUserIsStaffJob(user: $helper))->handle())->toBeFalse();
});
