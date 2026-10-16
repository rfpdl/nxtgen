<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum RelationshipEnum: string implements HasEnumMetadata
{
    use ProvidesEnumMetadata;

    case Mother = 'mother';
    case Father = 'father';
    case Guardian = 'guardian';
    case Grandparent = 'grandparent';
    case AuntUncle = 'aunt_uncle';
    case Sibling = 'sibling';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Mother => 'Mother',
            self::Father => 'Father',
            self::Guardian => 'Guardian',
            self::Grandparent => 'Grandparent',
            self::AuntUncle => 'Aunt / uncle',
            self::Sibling => 'Sibling',
            self::Other => 'Other',
        };
    }

    /** Shown as plain text, never as a coloured badge. */
    public function color(): string
    {
        return 'muted';
    }
}
