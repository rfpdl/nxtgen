<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum ScopeTypeEnum: string implements HasEnumMetadata
{
    use ProvidesEnumMetadata;

    case Church = 'church';
    case Satellite = 'satellite';
    case Schedule = 'schedule';

    public function label(): string
    {
        return match ($this) {
            self::Church => 'Church',
            self::Satellite => 'Satellite',
            self::Schedule => 'Class',
        };
    }

    /** Shown as plain text, never as a coloured badge. */
    public function color(): string
    {
        return 'muted';
    }
}
