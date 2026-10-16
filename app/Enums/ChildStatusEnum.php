<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasDefault;
use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum ChildStatusEnum: string implements HasDefault, HasEnumMetadata
{
    use ProvidesEnumMetadata;

    case Active = 'active';
    case Inactive = 'inactive';
    case Graduated = 'graduated';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Graduated => 'Graduated',
        };
    }

    public static function default(): self
    {
        return self::Active;
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'muted',
            self::Graduated => 'info',
        };
    }
}
