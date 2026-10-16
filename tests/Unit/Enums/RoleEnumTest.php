<?php

declare(strict_types=1);

use App\Enums\RoleEnum;

/* What a staff member may do: Admin manages people and families; a Lifeshaper looks after children at a class. */

it('stores admin and lifeshaper, the values every role assignment holds', function (): void {
    expect(array_column(RoleEnum::cases(), 'value'))->toBe(['admin', 'lifeshaper']);
});

it('names each role in the glossary words, as plain text', function (): void {
    expect(RoleEnum::toFrontend())->toBe([
        ['value' => 'admin', 'label' => 'Admin', 'color' => 'muted'],
        ['value' => 'lifeshaper', 'label' => 'Lifeshaper', 'color' => 'muted'],
    ]);
});
