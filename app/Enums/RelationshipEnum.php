<?php

declare(strict_types=1);

namespace App\Enums;

enum RelationshipEnum: string
{
    case Mother = 'mother';
    case Father = 'father';
    case Guardian = 'guardian';
    case Grandparent = 'grandparent';
    case AuntUncle = 'aunt_uncle';
    case Sibling = 'sibling';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Mother => 'Mother',
            self::Father => 'Father',
            self::Guardian => 'Guardian',
            self::Grandparent => 'Grandparent',
            self::AuntUncle => 'Aunt / uncle',
            self::Sibling => 'Sibling',
            self::Other => 'Other',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function toFrontend(): array
    {
        return array_map(
            static fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
