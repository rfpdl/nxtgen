<?php

declare(strict_types=1);

use App\Data\Models\Branch;
use App\Data\Models\Family;
use App\Data\Models\Guardian;
use App\Data\Models\Organization;
use App\Data\Models\User;
use Illuminate\Database\QueryException;

/*
 * The database, not the application, holds the rules that protect who can see which children
 * (plan §4.1). Each test here proves one constraint refuses a bad write.
 */

function familyIn(Organization $org): Family
{
    return Family::factory()->for($org)->for(Branch::factory()->for($org), 'homeBranch')->create();
}

it('refuses one user as a guardian in two families of the same church', function (): void {
    $org = Organization::factory()->create();
    $user = User::factory()->create();

    Guardian::factory()->for(familyIn($org))->for($org)->for($user)->create();

    expect(fn () => Guardian::factory()->for(familyIn($org))->for($org)->for($user)->create())
        ->toThrow(QueryException::class, 'guardians_organization_id_user_id_unique');
});

it('allows the same user as a guardian in families of two different churches', function (): void {
    $user = User::factory()->create();
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    Guardian::factory()->for(familyIn($orgA))->for($orgA)->for($user)->create();
    Guardian::factory()->for(familyIn($orgB))->for($orgB)->for($user)->create();

    expect(Guardian::query()->where('user_id', $user->id)->count())->toBe(2);
});

it('refuses a second login on the same family (one login per family in v1)', function (): void {
    $org = Organization::factory()->create();
    $family = familyIn($org);

    Guardian::factory()->for($family)->for($org)->for(User::factory())->create();

    expect(fn () => Guardian::factory()->for($family)->for($org)->for(User::factory())->create())
        ->toThrow(QueryException::class, 'guardians_login_family_key_unique');
});

it('allows several guardians without a login on one family', function (): void {
    $org = Organization::factory()->create();
    $family = familyIn($org);

    Guardian::factory()->count(3)->for($family)->for($org)->create(['user_id' => null]);

    expect($family->guardians()->count())->toBe(3);
});

it('refuses a primary guardian that belongs to another family', function (): void {
    $org = Organization::factory()->create();
    $family = familyIn($org);
    $stranger = Guardian::factory()->for(familyIn($org))->for($org)->create();

    expect(fn () => $family->forceFill(['primary_guardian_id' => $stranger->id])->save())
        ->toThrow(QueryException::class, 'families_primary_guardian_in_family');
});

it('accepts a primary guardian from the same family', function (): void {
    $org = Organization::factory()->create();
    $family = familyIn($org);
    $own = Guardian::factory()->for($family)->for($org)->create();

    $family->forceFill(['primary_guardian_id' => $own->id])->save();

    expect($family->fresh()?->primary_guardian_id)->toBe($own->id);
});
