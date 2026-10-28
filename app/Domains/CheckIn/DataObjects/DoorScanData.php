<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\DataObjects;

use App\Enums\CardMatchEnum;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A name card at the door (slice 3): the code it spells ("ADAMMATTHIASABERGOS"), how it matched, the child to tick —
 * only on ONE exact match (`child_ids` empty otherwise) — and what to tell staff when it is anything else.
 */
#[TypeScript]
final class DoorScanData extends Data
{
    /** @param list<int> $child_ids */
    public function __construct(
        public string $code,
        public CardMatchEnum $match,
        public array $child_ids,
        /** "No child matches this card: …", "Check this is the right child …"; null on one exact match. */
        public ?string $note,
    ) {}

    /** @param list<int> $matched the children the card named */
    public static function of(string $code, CardMatchEnum $match, array $matched): self
    {
        return new self(code: $code, match: $match, child_ids: $match->ticks() ? $matched : [], note: $match->note($code));
    }
}
