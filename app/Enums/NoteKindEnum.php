<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum NoteKindEnum: string implements HasEnumMetadata
{
    use ProvidesEnumMetadata;

    case System = 'system';
    case Observation = 'observation';

    public function label(): string
    {
        return match ($this) {
            self::System => 'System',
            self::Observation => 'Observation',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::System => 'muted',
            self::Observation => 'info',
        };
    }
}
