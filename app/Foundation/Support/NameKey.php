<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The name-card key (slice 3, DECISIONS 2026-10-28 night): the NXTGEN name cards carry a Code 39 barcode of the child's
 * full name in capitals with no spaces — "Adam Matthias Abergos" → ADAMMATTHIASABERGOS. So the key is first + middle +
 * last name, accents removed (ñ → N), letters A–Z only, upper-cased. A scanned or typed code is read the same way.
 */
final class NameKey
{
    /**
     * The stored keys' widths (ASCII columns): three name parts of up to 100 characters, each letter spelled out as at
     * most two (ß → SS, æ → AE), with room to spare — a longer spelling is cut, never a failed save.
     */
    public const int KEY_LENGTH = 640;

    public const int PART_KEY_LENGTH = 220;

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
        return [
            'name_key' => substr(self::of($first, $middle, $last), 0, self::KEY_LENGTH),
            'name_key_first' => substr(self::code($first), 0, self::PART_KEY_LENGTH),
            'name_key_last' => substr(self::code($last), 0, self::PART_KEY_LENGTH),
        ];
    }

    /**
     * Work out every child's keys from their names, in batches — what a migration runs when the keys are added or the
     * rule changes. Safe to run again: the same names give the same keys.
     */
    public static function fillIn(): void
    {
        DB::table('children')->select(['id', 'church_id', 'first_name', 'middle_name', 'last_name'])->orderBy('id')->chunkById(500, function ($children): void {
            foreach ($children as $child) {
                self::fillInRow($child);
            }
        });
    }

    /**
     * One child's keys, from the names `fillIn` read — written only if the row still has exactly those names. A name
     * edited since was saved with its own keys by the Child model; overwriting them with keys of the old name would
     * make a card tick the wrong child (Astra). False when nothing was written: the row had changed (left alone), or
     * already had these keys.
     */
    public static function fillInRow(object $child): bool
    {
        $first = isset($child->first_name) && is_string($child->first_name) ? $child->first_name : '';
        $middle = isset($child->middle_name) && is_string($child->middle_name) ? $child->middle_name : null;
        $last = isset($child->last_name) && is_string($child->last_name) ? $child->last_name : '';

        return DB::table('children')
            ->where('id', $child->id ?? 0)
            ->where('church_id', $child->church_id ?? 0)
            ->where('first_name', $first)
            ->whereRaw('middle_name <=> ?', [$middle])
            ->where('last_name', $last)
            ->update(self::keysFor($first, $middle, $last)) > 0;
    }

    /** A search term that could be a card's code: one word of letters only, at least two of them. */
    public static function looksLikeCode(string $term): bool
    {
        $term = trim($term);

        return preg_match('/^\p{L}+$/u', $term) === 1 && strlen(self::code($term)) >= 2;
    }
}
