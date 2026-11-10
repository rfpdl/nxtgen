<?php

declare(strict_types=1);

use App\Enums\SessionStateEnum;

/* Where today's Session of a class stands (spec item 15): not opened yet, open, or closed — and what the door says. */

it('has the three states', function (): void {
    expect(array_column(SessionStateEnum::cases(), 'value'))->toBe(['not_open', 'open', 'closed']);
});

it('says why nothing can change, in plain words — and nothing while open', function (): void {
    expect(SessionStateEnum::NotOpen->refusal('1PM'))->toBe('1PM is not open yet.')
        ->and(SessionStateEnum::Closed->refusal('1PM'))->toBe('1PM is closed.')
        ->and(SessionStateEnum::Open->refusal('1PM'))->toBeNull();
});

it('names each state for the screen, with its colour — the one wording the pages show (no copy on the frontend)', function (): void {
    expect(SessionStateEnum::toFrontend())->toBe([
        ['value' => 'not_open', 'label' => 'Not open yet', 'color' => 'muted'],
        ['value' => 'open', 'label' => 'Open', 'color' => 'success'],
        ['value' => 'closed', 'label' => 'Closed', 'color' => 'warning'],
    ]);
});
