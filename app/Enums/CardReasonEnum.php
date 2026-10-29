<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Why a Card request was made (GLOSSARY "Card request", DECISIONS 2026-10-29). Staff file Lost or Damaged; a First
 * card is made when a Child is registered (issue 02), and is free.
 */
#[TypeScript]
enum CardReasonEnum: string implements HasEnumMetadata
{
    use ProvidesEnumMetadata;

    /**
     * AED 10, in fils, for a lost or damaged card (plan 2026-10-14). A constant for now: the spec calls it a church
     * setting, which can replace this when a second church or a new price needs one.
     */
    public const int REPLACEMENT_FEE_MINOR = 1000;

    case Lost = 'lost';
    case Damaged = 'damaged';
    case FirstCard = 'first_card';

    public function label(): string
    {
        return match ($this) {
            self::Lost => 'Lost',
            self::Damaged => 'Damaged',
            self::FirstCard => 'First card',
        };
    }

    /** Shown as plain text, never as a coloured badge. */
    public function color(): string
    {
        return 'muted';
    }

    /** What the card costs, in fils: the first card is free. */
    public function feeMinor(): int
    {
        return match ($this) {
            self::Lost, self::Damaged => self::REPLACEMENT_FEE_MINOR,
            self::FirstCard => 0,
        };
    }

    /**
     * What staff may pick when they file a request: a first card is never filed by hand.
     *
     * @return list<self>
     */
    public static function staffChoices(): array
    {
        return [self::Lost, self::Damaged];
    }
}
