<?php

declare(strict_types=1);

namespace App\Domains\Registry\Values;

/** A guardian's "Complete Name" from the sheet, split at the last word: one word is all first name. */
final readonly class GuardianName
{
    public function __construct(public string $first, public string $last) {}

    public static function parse(string $full): self
    {
        $parts = preg_split('/\s+/', trim($full)) ?: [];
        if (count($parts) < 2) {
            return new self(trim($full), '');
        }
        $last = (string) array_pop($parts);

        return new self(implode(' ', $parts), $last);
    }
}
