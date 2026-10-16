<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum GenderEnum: string implements HasEnumMetadata
{
    use ProvidesEnumMetadata;

    case Boy = 'boy';
    case Girl = 'girl';

    public function label(): string
    {
        return match ($this) {
            self::Boy => 'Boy',
            self::Girl => 'Girl',
        };
    }

    /** Shown as plain text, never as a coloured badge. */
    public function color(): string
    {
        return 'muted';
    }
}
