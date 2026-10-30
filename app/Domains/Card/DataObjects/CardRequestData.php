<?php

declare(strict_types=1);

namespace App\Domains\Card\DataObjects;

use App\Data\Models\CardRequest;
use App\Enums\CardFeeEnum;
use App\Enums\CardMoveEnum;
use App\Enums\CardStepEnum;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A child's open Card request on the staff family page (name cards issue 01): where it is and whether it is paid.
 * `summary` is THE words for it — "Asked · not paid" — on the page's Pill and in the Feature's messages alike; `fee`
 * is what the Pill is coloured by. Staff only; the reason, note and who filed it are for the admins' list (issue 03).
 */
#[TypeScript]
final class CardRequestData extends Data
{
    /** The moves made from a child's card on the family page — by any staff who may open it, at the desk. */
    private const array DESK_MOVES = [CardMoveEnum::Give];

    public function __construct(
        public int $id,
        public int $child_id,
        public CardStepEnum $step,
        public CardFeeEnum $fee,
        public string $summary,
        /** @var list<CardMoveEnum> the moves offered at the desk now: Give, once printed (the rest are on Cards) */
        public array $moves,
    ) {}

    public static function fromModel(CardRequest $request): self
    {
        $fee = $request->fee();

        return new self(
            id: $request->id,
            child_id: $request->child_id,
            step: $request->step,
            fee: $fee,
            summary: "{$request->step->label()} · {$fee->label()}",
            moves: array_values(array_filter($request->moves(), fn (CardMoveEnum $m): bool => in_array($m, self::DESK_MOVES, true))),
        );
    }
}
