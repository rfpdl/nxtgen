<?php

declare(strict_types=1);

namespace App\Foundation\Support;

/**
 * A satellite's registration code (spec item 7, Roy 2026-11-08): 8 lowercase letters and digits, without the ones
 * that look alike (0/o, 1/i/l), carried by the satellite's printed QR poster and never changed. `/{code}/register`
 * forwards to the satellite's address, so a printed poster keeps working when the address changes.
 */
final class SatelliteCode
{
    public const string ALPHABET = '23456789abcdefghjkmnpqrstuvwxyz';

    public const int LENGTH = 8;

    public static function random(): string
    {
        $code = '';
        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return $code;
    }

    /**
     * Whether `$text` has a code's shape. A satellite address never may (Astra, issue 07): addresses are looked up
     * before codes, so an address shaped like a code could take over another satellite's printed QR.
     */
    public static function looksLikeOne(string $text): bool
    {
        return strlen($text) === self::LENGTH && strspn($text, self::ALPHABET) === self::LENGTH;
    }
}
