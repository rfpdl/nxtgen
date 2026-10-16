<?php

declare(strict_types=1);

use App\Data\Models\Guardian;
use App\Data\Models\User;
use App\Domains\Family\Jobs\UpdateFamilyPrimaryGuardianJob;
use Illuminate\Database\QueryException;

use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;

/* Which of a family's guardians is the primary one — and the database refuses one from another family. */

it('makes the guardian the family\'s primary', function (): void {
    $family = familyAt(satelliteOf(church()));
    guardianOf($family, User::factory()->create());
    $second = Guardian::factory()->create(['family_id' => $family->id]);

    (new UpdateFamilyPrimaryGuardianJob(family: $family, guardian: $second))->handle();

    expect($family->refresh()->primary_guardian_id)->toBe($second->id);
});

it('refuses a guardian from another family', function (): void {
    $satellite = satelliteOf(church());
    $family = familyAt($satellite);
    $stranger = Guardian::factory()->create(['family_id' => familyAt($satellite)->id]);

    expect(fn () => (new UpdateFamilyPrimaryGuardianJob(family: $family, guardian: $stranger))->handle())
        ->toThrow(QueryException::class, 'families_primary_guardian_in_family');
});
