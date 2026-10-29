<?php

declare(strict_types=1);

namespace App\Domains\Card\DataObjects;

use App\Data\Models\CardRequest;
use App\Enums\CardFeeEnum;
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
    public function __construct(
        public int $id,
        public int $child_id,
        public CardStepEnum $step,
        public CardFeeEnum $fee,
        public string $summary,
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
        );
    }
}
