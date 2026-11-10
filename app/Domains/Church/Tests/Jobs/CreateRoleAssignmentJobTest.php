<?php

declare(strict_types=1);

use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Domains\Church\Jobs\CreateRoleAssignmentJob;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;

use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\grantRole;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;

/* Giving a role where it is held — one Satellite or one class (spec item 9: no church-wide Role) — in that place's church. */

it('gives the Admin Role at a satellite, in the satellite\'s church, recording who gave it', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    $user = User::factory()->create();
    $admin = User::factory()->create();

    $added = (new CreateRoleAssignmentJob(user: $user, role: RoleEnum::Admin, place: $satellite, grantedBy: $admin))->handle();

    $row = RoleAssignment::query()->where('user_id', $user->id)->sole();
    expect($added)->toBeTrue()
        ->and([$row->church_id, $row->role, $row->scope_type, $row->scope_id, $row->is_active, $row->granted_by])
        ->toBe([$church->id, RoleEnum::Admin, ScopeTypeEnum::Satellite, $satellite->id, true, $admin->id]);
});

it('gives a class admin\'s role at a class, in the class\'s church', function (): void {
    $church = church();
    $class = scheduleAt(satelliteOf($church));
    $user = User::factory()->create();

    (new CreateRoleAssignmentJob(user: $user, role: RoleEnum::Admin, place: $class, grantedBy: User::factory()->create()))->handle();

    $row = RoleAssignment::query()->where('user_id', $user->id)->sole();
    expect([$row->church_id, $row->scope_type, $row->scope_id])->toBe([$church->id, ScopeTypeEnum::Schedule, $class->id]);
});

it('gives a satellite role when the scope is a satellite', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    $user = User::factory()->create();

    (new CreateRoleAssignmentJob(user: $user, role: RoleEnum::Lifeshaper, place: $satellite, grantedBy: User::factory()->create()))->handle();

    $row = RoleAssignment::query()->where('user_id', $user->id)->sole();
    expect([$row->scope_type, $row->scope_id])->toBe([ScopeTypeEnum::Satellite, $satellite->id]);
});

it('switches a removed role back on, and says no when the role is already held', function (): void {
    $church = church();
    $satellite = satelliteOf($church);
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Lifeshaper, atSatellite($satellite), active: false);
    $grant = fn (): bool => (new CreateRoleAssignmentJob(user: $user, role: RoleEnum::Lifeshaper, place: $satellite, grantedBy: User::factory()->create()))->handle();

    expect($grant())->toBeTrue()
        ->and(RoleAssignment::query()->where('user_id', $user->id)->sole()->is_active)->toBeTrue()
        ->and($grant())->toBeFalse()
        ->and(RoleAssignment::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('gives a helper for the day their Role at one Session, in its church — again after it was taken away, once (spec item 18)', function (): void {
    $satellite = satelliteOf(church());
    $session = sessionOf(scheduleAt($satellite));
    $admin = User::factory()->create();
    $helper = User::factory()->create();

    expect((new CreateRoleAssignmentJob(user: $helper, role: RoleEnum::Helper, place: $session, grantedBy: $admin))->handle())->toBeTrue();
    $role = RoleAssignment::query()->where('user_id', $helper->id)->sole();
    expect([$role->church_id, $role->scope_type, $role->scope_id, $role->granted_by])->toBe([$satellite->church_id, ScopeTypeEnum::Session, $session->id, $admin->id])
        ->and((new CreateRoleAssignmentJob(user: $helper, role: RoleEnum::Helper, place: $session, grantedBy: $admin))->handle())->toBeFalse();

    $role->update(['is_active' => false]);
    expect((new CreateRoleAssignmentJob(user: $helper, role: RoleEnum::Helper, place: $session, grantedBy: $admin))->handle())->toBeTrue()
        ->and(RoleAssignment::query()->where('user_id', $helper->id)->count())->toBe(1);
});
