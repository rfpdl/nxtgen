<?php

declare(strict_types=1);

namespace App\Domains\Card\Jobs;

use App\Data\Models\CardRequest;
use App\Data\Models\Child;
use App\Data\Models\User;
use App\Enums\CardReasonEnum;
use App\Enums\CardStepEnum;
use App\Foundation\Jobs\BaseJob;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * File a Card request for a Child (GLOSSARY "Card request"): Asked, with the reason's fee (AED 10 for lost or
 * damaged, a first card free), not paid, saying who and when. `$by` is null for a request the app makes itself.
 *
 * One OPEN request per Child, held by the database's unique `open_child_id`: when the insert meets it (one is open
 * already, or another phone filed at the same moment), the open request is handed back instead, with
 * `wasRecentlyCreated` false — so the caller can show it. As in CreateCheckInJob, it is read with a LOCKING read:
 * inside the caller's transaction (MySQL REPEATABLE READ) a plain read sees only its own snapshot, which cannot hold
 * the other phone's new row.
 *
 * @extends BaseJob<CardRequest>
 */
final class CreateCardRequestJob extends BaseJob
{
    public function __construct(
        private readonly Child $child,
        private readonly CardReasonEnum $reason,
        private readonly ?string $note,
        private readonly ?User $by,
    ) {}

    public function handle(): CardRequest
    {
        try {
            return CardRequest::query()->withSavepointIfNeeded(fn (): CardRequest => CardRequest::query()->create([
                'church_id' => $this->child->church_id,
                'child_id' => $this->child->id,
                'reason' => $this->reason,
                'note' => $this->note,
                'step' => CardStepEnum::default(),
                'fee_minor' => $this->reason->feeMinor(),
                'created_by' => $this->by?->id,
            ]));
        } catch (UniqueConstraintViolationException) {
            return CardRequest::query()->where('church_id', $this->child->church_id)->where('open_child_id', $this->child->id)->lockForUpdate()->firstOrFail();
        }
    }
}
