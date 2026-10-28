<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * How a name card matched at the door (slice 3): ONE child whose full name the card spells (exact — that child is
 * ticked); several such children; a close match (a middle name on one side only); or none. Only the exact one ticks a
 * child — anything else shows the families and asks staff to look (review, safety: never the wrong child ticked).
 */
#[TypeScript]
enum CardMatchEnum: string implements HasEnumMetadata
{
    use ProvidesEnumMetadata;

    case Exact = 'exact';
    case Several = 'several';
    case Close = 'close';
    case None = 'none';

    /** The card's child starts ticked on their family card. */
    public function ticks(): bool
    {
        return $this === self::Exact;
    }

    public function label(): string
    {
        return match ($this) {
            self::Exact => 'One child matches',
            self::Several => 'Several children match',
            self::Close => 'A close match',
            self::None => 'No child matches',
        };
    }

    /** What the door says under the search; nothing when one child matched exactly. */
    public function note(string $code): ?string
    {
        return match ($this) {
            self::Exact => null,
            self::Several => 'More than one child matches this card — tick the right one.',
            self::Close => 'Check this is the right child — the name on the card is not an exact match.',
            self::None => "No child matches this card: {$code}",
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Exact => 'success',
            self::Several, self::Close => 'warning',
            self::None => 'danger',
        };
    }
}
