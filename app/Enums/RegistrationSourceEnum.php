<?php

declare(strict_types=1);

namespace App\Enums;

enum RegistrationSourceEnum: string
{
    case Self = 'self';
    case Staff = 'staff';
    case Import = 'import';

    public function label(): string
    {
        return match ($this) {
            self::Self => 'Parent',
            self::Staff => 'Volunteer',
            self::Import => 'Imported',
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
