<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum DuplicateReasonEnum: string implements HasEnumMetadata
{
    use ProvidesEnumMetadata;

    case SamePhone = 'same_phone';
    case SameNameDob = 'same_name_dob';
    case SameEmail = 'same_email';

    public function label(): string
    {
        return match ($this) {
            self::SamePhone => 'Same phone',
            self::SameNameDob => 'Same name and birthday',
            self::SameEmail => 'Same email',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SamePhone => 'warning',
            self::SameNameDob => 'warning',
            self::SameEmail => 'warning',
        };
    }
}
