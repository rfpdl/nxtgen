<?php

declare(strict_types=1);

namespace App\Domains\Registry\Values;

use Illuminate\Support\Str;

/**
 * How the registry import reads one spreadsheet answer, shared by the raw export (RegistryCsvRecord) and the
 * reviewed file (ApprovedChildRowData): trimmed, a blank is null, and a yes/no answer is read from its first letter.
 */
final class SheetCell
{
    public static function text(?string $value): string
    {
        return trim($value ?? '');
    }

    public static function orNull(?string $value): ?string
    {
        $text = self::text($value);

        return $text === '' ? null : $text;
    }

    public static function yes(?string $value): bool
    {
        return Str::startsWith(mb_strtolower(self::text($value)), 'y');
    }
}
