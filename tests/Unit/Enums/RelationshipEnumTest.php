<?php

declare(strict_types=1);

use App\Enums\RelationshipEnum;

/* How a guardian is related to the family's children. */

it('stores the seven relationships, in the order the form offers them', function (): void {
    expect(array_column(RelationshipEnum::cases(), 'value'))
        ->toBe(['mother', 'father', 'guardian', 'grandparent', 'aunt_uncle', 'sibling', 'other']);
});

it('labels each as plain text', function (): void {
    expect(RelationshipEnum::AuntUncle->label())->toBe('Aunt / uncle')
        ->and(array_column(RelationshipEnum::toFrontend(), 'label'))
        ->toBe(['Mother', 'Father', 'Guardian', 'Grandparent', 'Aunt / uncle', 'Sibling', 'Other'])
        ->and(array_unique(array_column(RelationshipEnum::toFrontend(), 'color')))->toBe(['muted']);
});
