<?php

declare(strict_types=1);

namespace App\Foundation\DataObjects;

use App\Foundation\Support\LevelChart;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A Level as a page shows it (the Bold look, DECISIONS 2026-10-28): its number (1–4), which picks the pill's colour
 * from the age-level chart, and the name a person reads ("Level 2"). The one shape for a Level on every list, card
 * and Squad, so a pill never needs a second field beside the label.
 */
#[TypeScript]
final class LevelData extends Data
{
    public function __construct(
        public int $number,
        public string $name,
    ) {}

    public static function of(int $number): self
    {
        return new self(number: $number, name: LevelChart::name($number));
    }

    /** Null for a child with no Level yet. */
    public static function fromNumber(?int $number): ?self
    {
        return $number === null ? null : self::of($number);
    }
}
