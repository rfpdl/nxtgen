<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Illuminate\Support\Str;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A family's lifestage (Roy 2026-11-07). Optional: none given is null, never a fourth case. */
#[TypeScript]
enum LifestageEnum: string implements HasEnumMetadata
{
    use ProvidesEnumMetadata;

    case Single = 'single';
    case Married = 'married';
    case Ksp = 'ksp';

    /** A Registry cell, in any capitals and spacing; anything that is not one of the three is none given. */
    public static function fromLoose(?string $text): ?self
    {
        return self::tryFrom(Str::lower(Str::trim((string) $text)));
    }

    public function label(): string
    {
        return match ($this) {
            self::Single => 'Single',
            self::Married => 'Married',
            self::Ksp => 'KSP',
        };
    }

    /** Shown as plain text, never as a coloured badge. */
    public function color(): string
    {
        return 'muted';
    }
}
