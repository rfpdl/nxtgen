<?php

declare(strict_types=1);

use App\Domains\Family\DataObjects\ChildDetailsData;
use App\Domains\Family\Jobs\CreateChildJob;
use App\Enums\ChildStatusEnum;
use App\Enums\GenderEnum;
use App\Enums\RegistrationSourceEnum;

use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* A child on a family, in the family's church — names trimmed, blanks stored as nothing, nickname defaulted. */

it('creates the child in the family\'s church, trimming names and dropping blank details', function (): void {
    $family = familyAt(satelliteOf(church()));
    $details = new ChildDetailsData(
        firstName: '  Amara ', middleName: '   ', lastName: ' Santos ', nickname: '  ', gender: GenderEnum::Girl,
        dateOfBirth: '2019-03-04', allergies: ' peanuts ', illnesses: '', guardianNotes: null,
    );

    $child = (new CreateChildJob(family: $family, details: $details, level: 2, source: RegistrationSourceEnum::Staff))->handle();

    expect([$child->church_id, $child->family_id])->toBe([$family->church_id, $family->id])
        ->and([$child->first_name, $child->middle_name, $child->last_name, $child->nickname])->toBe(['Amara', null, 'Santos', 'Amara'])
        ->and([$child->allergies, $child->illnesses, $child->guardian_notes])->toBe(['peanuts', null, null])
        ->and($child->level)->toBe(2)
        ->and($child->level_set_at)->not->toBeNull()
        ->and($child->status)->toBe(ChildStatusEnum::default())
        ->and($child->registration_source)->toBe(RegistrationSourceEnum::Staff);
});

it('keeps a given nickname and status, and sets no level date without a level', function (): void {
    $family = familyAt(satelliteOf(church()));
    $details = new ChildDetailsData(
        firstName: 'Gabriel', middleName: null, lastName: 'Cruz', nickname: 'Gabby', gender: GenderEnum::Boy,
        dateOfBirth: '2018-01-01', allergies: null, status: ChildStatusEnum::Inactive,
    );

    $child = (new CreateChildJob(family: $family, details: $details, level: null, source: RegistrationSourceEnum::Import))->handle();

    expect($child->nickname)->toBe('Gabby')
        ->and($child->status)->toBe(ChildStatusEnum::Inactive)
        ->and($child->level)->toBeNull()
        ->and($child->level_set_at)->toBeNull();
});
