<?php

declare(strict_types=1);

namespace App\Enums;

enum MagicLinkPurposeEnum: string
{
    case Login = 'login';
    case VerifyEmail = 'verify_email';
    case ClaimFamily = 'claim_family';

    public function label(): string
    {
        return match ($this) {
            self::Login => 'Login',
            self::VerifyEmail => 'Confirm email',
            self::ClaimFamily => 'Claim family',
        };
    }
}
