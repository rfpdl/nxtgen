<?php

declare(strict_types=1);

use App\Enums\RegistrationSourceEnum;

/* Who registered a family: the parent, a staff member, or the registry import. */

it('stores the three sources', function (): void {
    expect(array_column(RegistrationSourceEnum::cases(), 'value'))->toBe(['self', 'staff', 'import']);
});

it('says who registered the family in the words people use', function (RegistrationSourceEnum $source, string $label): void {
    expect([$source->label(), $source->color()])->toBe([$label, 'muted']);
})->with([
    [RegistrationSourceEnum::Self, 'Parent'],
    [RegistrationSourceEnum::Staff, 'Volunteer'],
    [RegistrationSourceEnum::Import, 'Imported'],
]);
