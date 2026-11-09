<?php

declare(strict_types=1);

namespace App\Enums;

use App\Data\Models\Church;
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
    /** The fee, in fils: the church's card fee (the platform's Settings) for a lost or damaged card; a first card is free. */
    public function feeMinor(Church $church): int
    {
        return match ($this) {
            self::Lost, self::Damaged => $church->card_fee_minor,
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
