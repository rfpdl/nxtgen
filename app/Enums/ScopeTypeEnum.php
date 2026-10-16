<?php

declare(strict_types=1);

namespace App\Enums;

enum ScopeTypeEnum: string
{
    case Church = 'church';
    case Satellite = 'satellite';
    case Schedule = 'schedule';

    public function label(): string
    {
        return match ($this) {
            self::Church => 'Church',
            self::Satellite => 'Satellite',
            self::Schedule => 'Class',
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
