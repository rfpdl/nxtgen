<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\Organization\Jobs\GetAccessScopeJob;
use App\Enums\RoleEnum;

use function Tests\Support\atChurch;
use function Tests\Support\atSatellite;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\grantRole;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;
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
        ->and($dxb->organization_id)->toBe($church->id)
        ->and($dxb->name)->toBe('CCF DXB');
});

it('makes a satellite staff member who sees only their own satellite', function (): void {
    $church = church();
    $dxb = satelliteOf($church);
    $ours = familyAt($dxb);
    $theirs = familyAt(satelliteOf($church));

    $lifeshaper = staffMember(RoleEnum::Lifeshaper, atSatellite($dxb));
    $scope = (new GetAccessScopeJob(user: $lifeshaper, organization: $church))->handle();

    expect($scope->canSeeFamily($ours))->toBeTrue()
        ->and($scope->canSeeFamily($theirs))->toBeFalse();
});

it('gives a role only in the church its scope belongs to', function (): void {
    $ccf = church();
    $other = church();

    $admin = staffMember(RoleEnum::Admin, atChurch($ccf));

    expect((new GetAccessScopeJob(user: $admin, organization: $ccf))->handle()->canWorkHere())->toBeTrue()
        ->and((new GetAccessScopeJob(user: $admin, organization: $other))->handle()->canWorkHere())->toBeFalse();
});

it('gives an existing user a role, or a revoked one that opens nothing', function (): void {
    $church = church();
    $admin = User::factory()->create();
    $revoked = User::factory()->create();

    grantRole($admin, RoleEnum::Admin, atChurch($church));
    grantRole($revoked, RoleEnum::Admin, atChurch($church), active: false);

    expect((new GetAccessScopeJob(user: $admin, organization: $church))->handle()->canWorkHere())->toBeTrue()
        ->and((new GetAccessScopeJob(user: $revoked, organization: $church))->handle()->canWorkHere())->toBeFalse();
});

it('makes a signed-in guardian the primary guardian of their family', function (): void {
    $family = familyAt(satelliteOf(church()));
    $user = User::factory()->create();

    $guardian = guardianOf($family, $user, ['first_name' => 'Janice']);

    expect($family->refresh()->primary_guardian_id)->toBe($guardian->id)
        ->and($guardian->user_id)->toBe($user->id)
        ->and($guardian->organization_id)->toBe($family->organization_id)
        ->and($guardian->first_name)->toBe('Janice');
});

it('puts a child on the family, in the family\'s church', function (): void {
    $family = familyAt(satelliteOf(church()));

    $child = childOf($family, ['nickname' => 'Amara']);

    expect($child->family_id)->toBe($family->id)
        ->and($child->organization_id)->toBe($family->organization_id)
        ->and($child->nickname)->toBe('Amara');
});
