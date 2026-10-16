<?php

declare(strict_types=1);

use App\Enums\NoteKindEnum;

/* A history note is written by the app (a change was made) or by a person (something they saw). */

it('stores system and observation', function (): void {
    expect(array_column(NoteKindEnum::cases(), 'value'))->toBe(['system', 'observation']);
});

it('shows system notes muted and observations highlighted', function (NoteKindEnum $kind, string $label, string $color): void {
    expect([$kind->label(), $kind->color()])->toBe([$label, $color]);
})->with([
    [NoteKindEnum::System, 'System', 'muted'],
    [NoteKindEnum::Observation, 'Observation', 'info'],
]);
