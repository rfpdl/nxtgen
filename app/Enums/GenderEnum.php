<?php

declare(strict_types=1);

namespace App\Enums;

enum GenderEnum: string
{
    case Boy = 'boy';
    case Girl = 'girl';

    public function label(): string
    {
        return match ($this) {
            self::Boy => 'Boy',
            self::Girl => 'Girl',
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
