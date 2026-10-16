<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasDefault;
use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum NoteVisibilityEnum: string implements HasDefault, HasEnumMetadata
{
    use ProvidesEnumMetadata;

    case Staff = 'staff';
    case Family = 'family';

    public function label(): string
    {
        return match ($this) {
            self::Staff => 'Staff',
            self::Family => 'Family',
        };
    }

    public static function default(): self
    {
        return self::Staff;
    }

    public function color(): string
    {
        return match ($this) {
            self::Staff => 'muted',
            self::Family => 'info',
        };
    }
}
