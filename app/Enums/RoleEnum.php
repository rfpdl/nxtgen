<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum RoleEnum: string implements HasEnumMetadata
{
    use ProvidesEnumMetadata;

    case Admin = 'admin';
    case Lifeshaper = 'lifeshaper';
    /** For one Session only (spec item 18): Serve at its satellite while it is open, nothing after (Roy 2026-11-10). */
    case Helper = 'helper';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Lifeshaper => 'Lifeshaper',
            self::Helper => 'Helper for the day',
        };
    }

    /** Shown as plain text, never as a coloured badge. */
    public function color(): string
    {
        return 'muted';
    }
}
