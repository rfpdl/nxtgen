<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasDefault;
use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum FamilyClaimStatusEnum: string implements HasDefault, HasEnumMetadata
{
    use ProvidesEnumMetadata;

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

    public static function default(): self
    {
        return self::Pending;
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Accepted => 'success',
            self::Revoked => 'muted',
        };
    }
}
