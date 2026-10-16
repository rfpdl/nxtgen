<?php

declare(strict_types=1);

use App\Enums\NoteVisibilityEnum;

/* Who may read a note: staff only, or the family too. */

it('stores staff and family', function (): void {
    expect(array_column(NoteVisibilityEnum::cases(), 'value'))->toBe(['staff', 'family']);
});

it('keeps a note to staff unless it says otherwise', function (): void {
    expect(NoteVisibilityEnum::default())->toBe(NoteVisibilityEnum::Staff);
});

it('gives each visibility its label and colour', function (NoteVisibilityEnum $visibility, string $label, string $color): void {
    expect([$visibility->label(), $visibility->color()])->toBe([$label, $color]);
})->with([
    [NoteVisibilityEnum::Staff, 'Staff', 'muted'],
    [NoteVisibilityEnum::Family, 'Family', 'info'],
]);
