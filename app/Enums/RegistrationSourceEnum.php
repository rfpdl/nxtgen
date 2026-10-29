<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum RegistrationSourceEnum: string implements HasEnumMetadata
{
    use ProvidesEnumMetadata;

    case Self = 'self';
    case Staff = 'staff';
    case Import = 'import';

    public function label(): string
    {
        return match ($this) {
            self::Self => 'Parent',
            self::Staff => 'Volunteer',
            self::Import => 'Imported',
        };
    }

    /** Shown as plain text, never as a coloured badge. */
    public function color(): string
    {
        return 'muted';
    }

    /**
     * Whether a child registered this way gets a free first name card asked for (name cards 02, DECISIONS 2026-10-29):
     * yes when a parent or staff register them; no for the registry import — those children already have cards.
     */
    public function getsFirstCard(): bool
    {
        return $this !== self::Import;
    }
}
