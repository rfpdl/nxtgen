<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum MagicLinkPurposeEnum: string implements HasEnumMetadata
{
    use ProvidesEnumMetadata;

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

    /** Shown as plain text, never as a coloured badge. */
    public function color(): string
    {
        return 'muted';
    }
}
