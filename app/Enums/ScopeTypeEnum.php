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

    case Satellite = 'satellite';
    case Schedule = 'schedule';
    /** One class on one day — a helper for the day's (spec item 18). */
    case Session = 'session';

    public function label(): string
    {
        return match ($this) {
            self::Satellite => 'Satellite',
            self::Schedule => 'Class',
            self::Session => 'Class today',
        };
    }

    /** Shown as plain text, never as a coloured badge. */
    public function color(): string
    {
        return 'muted';
    }
}
