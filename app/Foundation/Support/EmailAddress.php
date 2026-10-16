<?php

declare(strict_types=1);

namespace App\Foundation\Support;

/**
 * The ONE way an email address is compared and stored: trimmed and lower-cased (plan §4.2 — one login per address,
 * one family per address per church). Logins, guardians, claims and the import all go through this.
 */
final class EmailAddress
{
    public static function normalise(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
