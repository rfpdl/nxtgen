<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasDefault;
use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum DuplicateStatusEnum: string implements HasDefault, HasEnumMetadata
{
    use ProvidesEnumMetadata;

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

    public static function default(): self
    {
        return self::Open;
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'warning',
            self::Dismissed => 'muted',
            self::Confirmed => 'danger',
        };
    }
}
