<?php

declare(strict_types=1);

use App\Enums\GenderEnum;

/* A child's gender — from Level 2 up it picks the child's Squad. */

it('stores boy and girl, the values the Squad chart uses', function (): void {
    expect(array_column(GenderEnum::cases(), 'value'))->toBe(['boy', 'girl']);
});

it('labels each as plain text', function (): void {
    expect(GenderEnum::toFrontend())->toBe([
        ['value' => 'boy', 'label' => 'Boy', 'color' => 'muted'],
        ['value' => 'girl', 'label' => 'Girl', 'color' => 'muted'],
    ]);
});
