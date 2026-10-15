<?php

declare(strict_types=1);

namespace App\Enums;

enum NoteKindEnum: string
{
    case System = 'system';
    case Observation = 'observation';

    public function label(): string
    {
        return match ($this) {
            self::System => 'System',
            self::Observation => 'Observation',
        };
    }
}
