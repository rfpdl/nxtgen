<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use App\Enums\GenderEnum;
use App\Enums\RelationshipEnum;

/**
 * The enums every family form needs — the parent's relationship and each child's gender — in ONE place, for the
 * four pages that show one (parent and staff registration, the parent's and the staff family page). Shared as
 * the page's `enums` prop and read with `useEnum('relationship' | 'gender')`.
 */
final class FamilyFormEnums
{
    /** @return array{relationship: list<array{value: string, label: string, color: string}>, gender: list<array{value: string, label: string, color: string}>} */
    public static function toFrontend(): array
    {
        return ['relationship' => RelationshipEnum::toFrontend(), 'gender' => GenderEnum::toFrontend()];
    }
}
