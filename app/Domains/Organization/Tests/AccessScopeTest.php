<?php

declare(strict_types=1);

use App\Data\Models\Branch;
use App\Data\Models\Family;
use App\Data\Models\Organization;
use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Domains\Organization\Jobs\GetAccessScopeJob;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;

/*
 * The access matrix (plan §3.2), one rule per test. AccessScope is the ONE owner of these answers;
 * Features ask it and never compare roles themselves.
 */

function grant(User $user, Organization $org, RoleEnum $role, ScopeTypeEnum $type, int $id, bool $active = true): void
{
    RoleAssignment::factory()->create(['organization_id' => $org->id, 'user_id' => $user->id, 'role' => $role, 'scope_type' => $type, 'scope_id' => $id, 'is_active' => $active]);
}

function homeFamily(Organization $org, Branch $branch): Family
{
    return Family::factory()->create(['organization_id' => $org->id, 'home_branch_id' => $branch->id]);
}

/** @return array{org: Organization, htc: Branch, house: Branch} */
function fx(): array
{
    $org = Organization::factory()->create();

    return ['org' => $org, 'htc' => Branch::factory()->for($org)->create(), 'house' => Branch::factory()->for($org)->create()];
}

it('gives a church admin every family and every edit', function (): void {
    ['org' => $org, 'htc' => $htc, 'house' => $house] = fx();
    $user = User::factory()->create();
    grant($user, $org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id);

    $scope = (new GetAccessScopeJob(user: $user, organization: $org))->handle();

    expect($scope->canWorkHere())->toBeTrue()
        ->and($scope->visibleBranchIds())->toBeNull()
        ->and($scope->canEditFamily(homeFamily($org, $htc)))->toBeTrue()
        ->and($scope->canEditFamily(homeFamily($org, $house)))->toBeTrue()
        ->and($scope->canMergeRecords())->toBeTrue()
        ->and($scope->canManageChurch())->toBeTrue();
});

it('limits a satellite admin to families of that satellite', function (): void {
    ['org' => $org, 'htc' => $htc, 'house' => $house] = fx();
    $user = User::factory()->create();
    grant($user, $org, RoleEnum::Admin, ScopeTypeEnum::Branch, $htc->id);

    $scope = (new GetAccessScopeJob(user: $user, organization: $org))->handle();

    expect($scope->visibleBranchIds())->toBe([$htc->id])
        ->and($scope->canEditFamily(homeFamily($org, $htc)))->toBeTrue()
        ->and($scope->canEditFamily(homeFamily($org, $house)))->toBeFalse()
        ->and($scope->canSeeFamily(homeFamily($org, $house)))->toBeFalse()
        ->and($scope->canMergeRecords())->toBeFalse()
        ->and($scope->canManageChurch())->toBeFalse();
});

it('lets a Lifeshaper see their satellite\'s families but not edit them', function (): void {
    ['org' => $org, 'htc' => $htc] = fx();
    $user = User::factory()->create();
    grant($user, $org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id);

    $scope = (new GetAccessScopeJob(user: $user, organization: $org))->handle();
    $family = homeFamily($org, $htc);

    expect($scope->canWorkHere())->toBeTrue()
        ->and($scope->canSeeFamily($family))->toBeTrue()
        ->and($scope->canEditFamily($family))->toBeFalse()
        ->and($scope->canRegisterFamilies())->toBeTrue();
});

it('combines several satellites', function (): void {
    ['org' => $org, 'htc' => $htc, 'house' => $house] = fx();
    $user = User::factory()->create();
    grant($user, $org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id);
    grant($user, $org, RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $house->id);

    $ids = (new GetAccessScopeJob(user: $user, organization: $org))->handle()->visibleBranchIds();

    expect($ids)->toEqualCanonicalizing([$htc->id, $house->id]);
});

it('ignores revoked roles and roles in another church', function (): void {
    ['org' => $org, 'htc' => $htc] = fx();
    $user = User::factory()->create();
    grant($user, $org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id, active: false);
    $other = Organization::factory()->create();
    grant($user, $other, RoleEnum::Admin, ScopeTypeEnum::Organization, $other->id);

    $scope = (new GetAccessScopeJob(user: $user, organization: $org))->handle();

    expect($scope->canWorkHere())->toBeFalse()
        ->and($scope->visibleBranchIds())->toBe([])
        ->and($scope->canSeeFamily(homeFamily($org, $htc)))->toBeFalse();
});

it('never lets a family from another church through, even for a church admin', function (): void {
    ['org' => $org] = fx();
    $user = User::factory()->create();
    grant($user, $org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id);
    $foreign = Family::factory()->create();

    $scope = (new GetAccessScopeJob(user: $user, organization: $org))->handle();

    expect($scope->canSeeFamily($foreign))->toBeFalse()
        ->and($scope->canEditFamily($foreign))->toBeFalse();
});

/*
 * People (plan §3.2 "Grant roles" + the role-grant ceiling): a church admin grants anything in the church;
 * a satellite admin grants only Lifeshaper, only in their own satellite; nobody else grants at all.
 */
it('lets a church admin grant any role anywhere in the church', function (): void {
    ['org' => $org, 'htc' => $htc] = fx();
    $user = User::factory()->create();
    grant($user, $org, RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id);

    $scope = (new GetAccessScopeJob(user: $user, organization: $org))->handle();

    expect($scope->canManagePeople())->toBeTrue()
        ->and($scope->canGrant(RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id))->toBeTrue()
        ->and($scope->canGrant(RoleEnum::Admin, ScopeTypeEnum::Branch, $htc->id))->toBeTrue()
        ->and($scope->canGrant(RoleEnum::Lifeshaper, ScopeTypeEnum::Organization, $org->id))->toBeTrue()
        ->and($scope->canGrant(RoleEnum::Admin, ScopeTypeEnum::Organization, $org->id + 999))->toBeFalse();
});

it('caps a satellite admin at Lifeshaper in their own satellite', function (): void {
    ['org' => $org, 'htc' => $htc, 'house' => $house] = fx();
    $user = User::factory()->create();
    grant($user, $org, RoleEnum::Admin, ScopeTypeEnum::Branch, $htc->id);

    $scope = (new GetAccessScopeJob(user: $user, organization: $org))->handle();

    expect($scope->canManagePeople())->toBeTrue()
        ->and($scope->canGrant(RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id))->toBeTrue()
        ->and($scope->canGrant(RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $house->id))->toBeFalse()
        ->and($scope->canGrant(RoleEnum::Admin, ScopeTypeEnum::Branch, $htc->id))->toBeFalse()
        ->and($scope->canGrant(RoleEnum::Lifeshaper, ScopeTypeEnum::Organization, $org->id))->toBeFalse();
});

it('does not let a Lifeshaper manage people', function (): void {
    ['org' => $org, 'htc' => $htc] = fx();
    $user = User::factory()->create();
    grant($user, $org, RoleEnum::Lifeshaper, ScopeTypeEnum::Organization, $org->id);

    $scope = (new GetAccessScopeJob(user: $user, organization: $org))->handle();

    expect($scope->canManagePeople())->toBeFalse()
        ->and($scope->canGrant(RoleEnum::Lifeshaper, ScopeTypeEnum::Branch, $htc->id))->toBeFalse();
});
