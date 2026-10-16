<?php

declare(strict_types=1);

use App\Data\Models\Guardian;
use App\Data\Models\User;
use Illuminate\Database\QueryException;

use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/*
 * The database, not the application, holds the rules that protect who can see which children
 * (plan §4.1). Each test here proves one constraint refuses a bad write.
 */

it('refuses one user as a guardian in two families of the same church', function (): void {
    $church = church();
    $user = User::factory()->create();

    Guardian::factory()->for(familyAt(satelliteOf($church)))->for($church)->for($user)->create();

    expect(fn () => Guardian::factory()->for(familyAt(satelliteOf($church)))->for($church)->for($user)->create())
        ->toThrow(QueryException::class, 'guardians_church_id_user_id_unique');
});

it('allows the same user as a guardian in families of two different churches', function (): void {
    $user = User::factory()->create();
    $churchA = church();
    $churchB = church();

    Guardian::factory()->for(familyAt(satelliteOf($churchA)))->for($churchA)->for($user)->create();
    Guardian::factory()->for(familyAt(satelliteOf($churchB)))->for($churchB)->for($user)->create();

    expect(Guardian::query()->where('user_id', $user->id)->count())->toBe(2);
});

it('refuses a second login on the same family (one login per family in v1)', function (): void {
    $church = church();
    $family = familyAt(satelliteOf($church));

    Guardian::factory()->for($family)->for($church)->for(User::factory())->create();

    expect(fn () => Guardian::factory()->for($family)->for($church)->for(User::factory())->create())
        ->toThrow(QueryException::class, 'guardians_login_family_key_unique');
});

it('allows several guardians without a login on one family', function (): void {
    $church = church();
    $family = familyAt(satelliteOf($church));

    Guardian::factory()->count(3)->for($family)->for($church)->create(['user_id' => null]);

    expect($family->guardians()->count())->toBe(3);
});

it('refuses a primary guardian that belongs to another family', function (): void {
    $church = church();
    $family = familyAt(satelliteOf($church));
    $stranger = Guardian::factory()->for(familyAt(satelliteOf($church)))->for($church)->create();

    expect(fn () => $family->forceFill(['primary_guardian_id' => $stranger->id])->save())
        ->toThrow(QueryException::class, 'families_primary_guardian_in_family');
});

it('accepts a primary guardian from the same family', function (): void {
    $church = church();
    $family = familyAt(satelliteOf($church));
    $own = Guardian::factory()->for($family)->for($church)->create();

    $family->forceFill(['primary_guardian_id' => $own->id])->save();

    expect($family->fresh()?->primary_guardian_id)->toBe($own->id);
});
