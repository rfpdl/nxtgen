<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\User\Jobs\GetUserIsStaffJob;
use App\Enums\RoleEnum;

use function Tests\Support\atChurch;
use function Tests\Support\church;
use function Tests\Support\grantRole;
use function Tests\Support\staffMember;

/* Staff = an active role in any church, or the platform admin. A revoked role does not count. */

it('is true for an active role in any church and for the platform admin', function (): void {
    $lifeshaper = staffMember(RoleEnum::Lifeshaper, atChurch(church()));

    expect((new GetUserIsStaffJob(user: $lifeshaper))->handle())->toBeTrue()
        ->and((new GetUserIsStaffJob(user: User::factory()->platformAdmin()->create()))->handle())->toBeTrue();
});

it('is false for a login with no role, or only a revoked one', function (): void {
    $revoked = User::factory()->create();
    grantRole($revoked, RoleEnum::Admin, atChurch(church()), active: false);

    expect((new GetUserIsStaffJob(user: User::factory()->create()))->handle())->toBeFalse()
        ->and((new GetUserIsStaffJob(user: $revoked))->handle())->toBeFalse();
});
