<?php

declare(strict_types=1);

namespace App\Foundation\Support;

/**
 * "Which email did we use?" without revealing it to everyone (plan §4): `janice@example.com` → `ja•••@example.com`.
 */
final class EmailMask
{
    public static function mask(string $email): string
    {
        $at = mb_strrpos($email, '@');
        if ($at === false) {
            return '•••';
        }

        return mb_substr($email, 0, min(2, $at)).'•••'.mb_substr($email, $at);
    }
}
