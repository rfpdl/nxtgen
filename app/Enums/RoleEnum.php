<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleEnum: string
{
    case Admin = 'admin';
    case Lifeshaper = 'lifeshaper';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Lifeshaper => 'Lifeshaper',
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
