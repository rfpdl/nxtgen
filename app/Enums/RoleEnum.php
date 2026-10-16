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

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Lifeshaper => 'Lifeshaper',
        };
    }

    /** Shown as plain text, never as a coloured badge. */
    public function color(): string
    {
        return 'muted';
    }
}
