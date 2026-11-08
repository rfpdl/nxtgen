<?php

declare(strict_types=1);

use App\Data\Models\RoleAssignment;
use App\Domains\Church\Jobs\GetAccessScopeJob;
use App\Domains\Church\Jobs\UpdateRoleAssignmentAsInactiveJob;
use App\Enums\RoleEnum;

use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\staffMember;

/* Removing a role keeps its row, switched off — and the person loses the access it gave. */

it('switches the role off, keeps the row, and takes the access away', function (): void {
    $satellite = satelliteOf(church());
    $user = staffMember(RoleEnum::Admin, atSatellite($satellite));
    $role = RoleAssignment::query()->where('user_id', $user->id)->sole();

    $removed = (new UpdateRoleAssignmentAsInactiveJob(assignment: $role))->handle();

    expect($removed->is_active)->toBeFalse()
        ->and(RoleAssignment::query()->whereKey($role->id)->sole()->is_active)->toBeFalse()
        ->and((new GetAccessScopeJob(user: $user, satellite: $satellite))->handle()->canWorkHere())->toBeFalse();
});

it('leaves the person\'s other roles alone', function (): void {
    $satellite = satelliteOf(church());
    $user = staffMember(RoleEnum::Admin, atSatellite($satellite));
    $other = staffMember(RoleEnum::Admin, atSatellite($satellite));
    $role = RoleAssignment::query()->where('user_id', $user->id)->sole();

    (new UpdateRoleAssignmentAsInactiveJob(assignment: $role))->handle();

    expect(RoleAssignment::query()->where('user_id', $other->id)->sole()->is_active)->toBeTrue();
});
