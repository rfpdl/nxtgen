<?php

declare(strict_types=1);

namespace App\Enums;

enum NoteVisibilityEnum: string
{
    case Staff = 'staff';
    case Family = 'family';

    public function label(): string
    {
        return match ($this) {
            self::Staff => 'Staff',
            self::Family => 'Family',
        };
    }
}
