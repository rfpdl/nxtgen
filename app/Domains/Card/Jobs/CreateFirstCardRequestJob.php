<?php

declare(strict_types=1);

namespace App\Domains\Card\Jobs;

use App\Data\Models\CardRequest;
use App\Data\Models\Child;
use App\Enums\CardReasonEnum;
use App\Enums\CardStepEnum;
use App\Enums\RegistrationSourceEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * A newly registered Child's free first name card (name cards 02, DECISIONS 2026-10-29): Asked, free, asked for by
 * the app (no `created_by`) — or nothing for an imported child, who already has a card
 * (RegistrationSourceEnum::getsFirstCard()). The one home of that rule: registering a family and adding a child both
 * run this right after CreateChildJob, inside their transaction.
 *
 * The child is new, so no open request can be in the way; should one ever be, the unique key's error is NOT caught —
 * the caller's transaction then undoes the child as well, rather than leave a new child without a first card.
 *
 * @extends BaseJob<CardRequest|null>
 */
final class CreateFirstCardRequestJob extends BaseJob
{
    public function __construct(
        private readonly Child $child,
        private readonly RegistrationSourceEnum $source,
    ) {}

    public function handle(): ?CardRequest
    {
        if (! $this->source->getsFirstCard()) {
            return null;
        }

        return CardRequest::query()->create([
            'church_id' => $this->child->church_id,
            'child_id' => $this->child->id,
            'reason' => CardReasonEnum::FirstCard,
            'note' => null,
            'step' => CardStepEnum::default(),
            'fee_minor' => CardReasonEnum::FirstCard->feeMinor(),
            'created_by' => null,
        ]);
    }
}
