<?php

declare(strict_types=1);

namespace App\Enums;

enum DuplicateStatusEnum: string
{
    case Open = 'open';
    case Dismissed = 'dismissed';
    case Confirmed = 'confirmed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Dismissed => 'Dismissed',
            self::Confirmed => 'Confirmed',
        };
    }
}
