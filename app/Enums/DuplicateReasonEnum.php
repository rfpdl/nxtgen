<?php

declare(strict_types=1);

namespace App\Enums;

enum DuplicateReasonEnum: string
{
    case SamePhone = 'same_phone';
    case SameNameDob = 'same_name_dob';
    case SameEmail = 'same_email';

    public function label(): string
    {
        return match ($this) {
            self::SamePhone => 'Same phone',
            self::SameNameDob => 'Same name and birthday',
            self::SameEmail => 'Same email',
        };
    }
}
