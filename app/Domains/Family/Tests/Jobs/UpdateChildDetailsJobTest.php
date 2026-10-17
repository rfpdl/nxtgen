<?php

declare(strict_types=1);

use App\Data\Models\Note;
use App\Domains\Family\DataObjects\ChildDetailsData;
use App\Domains\Family\Jobs\UpdateChildDetailsJob;
use App\Enums\ChildStatusEnum;
use App\Enums\GenderEnum;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* The details a parent may change on a child — trimmed, blanks as empty — and never the status or the Level. */

it('saves the details trimmed, blanks as null and an empty nickname as the first name', function (): void {
    $child = childOf(familyAt(satelliteOf(church())), ['allergies' => 'Peanuts', 'fears' => 'Dogs']);

    (new UpdateChildDetailsJob(child: $child, details: new ChildDetailsData(
        firstName: ' Amara ', middleName: '  ', lastName: ' Santos ', nickname: ' ', gender: GenderEnum::Girl,
        dateOfBirth: '2019-03-04', allergies: ' Shellfish ', illnesses: null, dislikes: 'Loud music', fears: '', guardianNotes: ' Shy at first ',
    )))->handle();

    $child->refresh();
    expect([$child->first_name, $child->middle_name, $child->last_name, $child->nickname])->toBe(['Amara', null, 'Santos', 'Amara'])
        ->and($child->gender)->toBe(GenderEnum::Girl)
        ->and($child->date_of_birth->toDateString())->toBe('2019-03-04')
        ->and([$child->allergies, $child->illnesses, $child->dislikes, $child->fears, $child->guardian_notes])
        ->toBe(['Shellfish', null, 'Loud music', null, 'Shy at first'])
        ->and(Note::query()->count())->toBe(0);
});

it('leaves the status and the Level alone, even when the details carry a status', function (): void {
    $child = childOf(familyAt(satelliteOf(church())), ['status' => ChildStatusEnum::Active, 'level' => 2]);

    (new UpdateChildDetailsJob(child: $child, details: new ChildDetailsData(
        firstName: 'Amara', middleName: null, lastName: 'Santos', nickname: 'Mimi', gender: GenderEnum::Girl,
        dateOfBirth: '2019-03-04', allergies: null, status: ChildStatusEnum::Graduated,
    )))->handle();

    $child->refresh();
    expect($child->status)->toBe(ChildStatusEnum::Active)
        ->and($child->level)->toBe(2)
        ->and($child->nickname)->toBe('Mimi');
});
