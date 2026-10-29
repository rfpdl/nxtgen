<?php

declare(strict_types=1);

use App\Data\Models\CardRequest;
use App\Data\Models\Child;
use App\Domains\Family\DataObjects\ChildDetailsData;
use App\Enums\CardReasonEnum;
use App\Enums\CardStepEnum;
use App\Enums\GenderEnum;
use App\Enums\RegistrationSourceEnum;
use App\Operations\Family\AddChildToFamilyOperation;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/*
 * Adding a child to a family already registered (parent page, staff after a search, the registry import). Name cards
 * 02: the new child — and only the new child — gets a free first card asked for, unless imported.
 */

function newChild(): ChildDetailsData
{
    return new ChildDetailsData(firstName: 'Clara', middleName: null, lastName: 'Ramos', nickname: 'Cla', gender: GenderEnum::Girl, dateOfBirth: '2021-03-01', allergies: null);
}

it('asks for a free first card for the added child only, made by the app', function (RegistrationSourceEnum $source): void {
    $family = familyAt(satelliteOf(church()));
    $amara = childOf($family, ['nickname' => 'Amara']);

    $clara = (new AddChildToFamilyOperation(family: $family, details: newChild(), source: $source))->handle();

    $request = CardRequest::query()->sole();
    expect($clara->id)->not->toBe($amara->id)
        ->and([$request->child_id, $request->church_id, $request->reason, $request->step, $request->fee_minor, $request->created_by])
        ->toBe([$clara->id, $family->church_id, CardReasonEnum::FirstCard, CardStepEnum::Asked, 0, null]);
})->with([RegistrationSourceEnum::Self, RegistrationSourceEnum::Staff]);

it('asks for none for an imported child — they already have a card', function (): void {
    $family = familyAt(satelliteOf(church()));

    (new AddChildToFamilyOperation(family: $family, details: newChild(), source: RegistrationSourceEnum::Import, level: 1))->handle();

    expect(Child::query()->where('family_id', $family->id)->count())->toBe(1)
        ->and(CardRequest::query()->count())->toBe(0);
});

it('adds no child when the first card cannot be asked for — both or neither', function (): void {
    $family = familyAt(satelliteOf(church()));
    CardRequest::creating(fn (): never => throw new RuntimeException('The card request was refused.'));

    expect(fn () => (new AddChildToFamilyOperation(family: $family, details: newChild(), source: RegistrationSourceEnum::Self))->handle())
        ->toThrow(RuntimeException::class, 'The card request was refused.')
        ->and(Child::query()->where('family_id', $family->id)->count())->toBe(0);

    CardRequest::flushEventListeners();
});
