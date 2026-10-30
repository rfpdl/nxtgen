<?php

declare(strict_types=1);

namespace App\Domains\Card\DataObjects;

use App\Data\Models\CardRequest;
use App\Enums\CardFeeEnum;
use App\Enums\CardMoveEnum;
use App\Enums\CardStepEnum;
use App\Foundation\Support\Money;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One row of the Cards page (name cards 03), for an admin: the child's full name (as the card spells it), their
 * family and satellite, why the card was asked for (and the note), the fee — "AED 10 · not paid", "AED 10 · paid" or
 * "Free" — and, once refused, why. `moves` are what may be done now — the screen offers exactly these.
 */
#[TypeScript]
final class CardRequestRowData extends Data
{
    public function __construct(
        public int $id,
        public string $child_name,
        public int $family_id,
        public string $family_name,
        public string $satellite,
        public string $reason_label,
        public ?string $note,
        public CardStepEnum $step,
        public string $step_label,
        public CardFeeEnum $fee,
        public string $fee_label,
        public ?string $refused_reason,
        /** @var list<CardMoveEnum> the moves the server allows now (CardMoveEnum) — the row offers these, no others */
        public array $moves,
    ) {}

    public static function fromRequest(CardRequest $request): self
    {
        $family = $request->child->family;
        $fee = $request->fee();

        return new self(
            id: $request->id,
            child_name: $request->child->fullName(),
            family_id: $family->id,
            family_name: $family->contactName(),
            satellite: $family->homeSatellite->name,
            reason_label: $request->reason->label(),
            note: $request->note,
            step: $request->step,
            step_label: $request->step->label(),
            fee: $fee,
            fee_label: $fee === CardFeeEnum::Free ? 'Free' : Money::aed($request->fee_minor)." · {$fee->label()}",
            refused_reason: $request->refused_reason,
            moves: $request->moves(),
        );
    }
}
