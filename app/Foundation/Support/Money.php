<?php

declare(strict_types=1);

namespace App\Foundation\Support;

/**
 * An amount stored in fils (minor units), as people read it: "AED 10", "AED 10.50" — whole dirhams without decimals,
 * fils never dropped. The one formatter for money on screen and in messages (name cards: the AED 10 fee).
 */
final class Money
{
    public static function aed(int $minor): string
    {
        $decimals = $minor % 100 === 0 ? 0 : 2;

        return 'AED '.number_format($minor / 100, $decimals);
    }
}
