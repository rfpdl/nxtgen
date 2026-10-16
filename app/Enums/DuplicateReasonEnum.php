<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Why two families or children were flagged as possibly the same. Not the same email: a second registration
 * with a known email goes through the claim path instead (plan §4.2).
 */
#[TypeScript]
enum DuplicateReasonEnum: string implements HasEnumMetadata
{
    use ProvidesEnumMetadata;

    case SamePhone = 'same_phone';
    case SameNameDob = 'same_name_dob';

    public function label(): string
    {
        return match ($this) {
            self::SamePhone => 'Same phone',
            self::SameNameDob => 'Same name and birthday',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SamePhone => 'warning',
            self::SameNameDob => 'warning',
        };
    }
}
