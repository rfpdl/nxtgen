<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use Illuminate\Support\Str;

/**
 * The name-card key (slice 3, DECISIONS 2026-10-28 night): the NXTGEN name cards carry a Code 39 barcode of the child's
 * full name in capitals with no spaces — "Adam Matthias Abergos" → ADAMMATTHIASABERGOS. So the key is first + middle +
 * last name, accents removed (ñ → N), letters A–Z only, upper-cased. A scanned or typed code is read the same way.
 */
final class NameKey
{
    /** A close match (a middle name on one side only) is tried only for a code at least this long. */
    public const int CLOSE_MATCH_MIN_LETTERS = 6;

    public static function of(string $first, ?string $middle, string $last): string
    {
        return self::code($first.($middle ?? '').$last);
    }

    /** Any text as a key: accents removed, letters A–Z only, upper-cased. */
    public static function code(string $text): string
    {
        return (string) preg_replace('/[^A-Z]/', '', Str::upper(Str::ascii($text)));
    }

    /**
     * The keys a child is stored with (the Child model on every save, and the migration that filled them in): the full
     * name, and the first and last names alone (for a close match).
     *
     * @return array{name_key: string, name_key_first: string, name_key_last: string}
     */
    public static function keysFor(string $first, ?string $middle, string $last): array
    {
        return ['name_key' => self::of($first, $middle, $last), 'name_key_first' => self::code($first), 'name_key_last' => self::code($last)];
    }

    /** A search term that could be a card's code: one word of letters only, at least two of them. */
    public static function looksLikeCode(string $term): bool
    {
        $term = trim($term);

        return preg_match('/^\p{L}+$/u', $term) === 1 && strlen(self::code($term)) >= 2;
    }
}
