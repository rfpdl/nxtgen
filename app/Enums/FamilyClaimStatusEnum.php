<?php

declare(strict_types=1);

namespace App\Enums;

enum FamilyClaimStatusEnum: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Accepted => 'Accepted',
            self::Revoked => 'Revoked',
        };
    }
}
