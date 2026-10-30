<?php

declare(strict_types=1);

namespace App\Domains\Card\Jobs;

use App\Data\Models\CardRequest;
use App\Data\Models\User;
use App\Enums\CardMoveEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * Mark a Card request's fee paid (cash at the desk, DECISIONS 2026-10-29), saying who and when: while it is open, not
 * paid yet, and not free.
 *
 * One conditional UPDATE from CardMoveEnum::MarkPaid's steps — see CardMoveEnum for why, and for what keeps out another
 * church's request.
 *
 * @extends BaseJob<bool>
 */
final class UpdateCardRequestAsPaidJob extends BaseJob
{
    public function __construct(
        private readonly CardRequest $request,
        private readonly User $by,
    ) {}

    /** Whether it moved: false = the request was not where this move starts (moved on meanwhile, or never there). */
    public function handle(): bool
    {
        return CardRequest::query()
            ->whereKey($this->request->id)
            ->where('church_id', $this->request->church_id)
            ->whereIn('step', CardMoveEnum::MarkPaid->fromStepValues())
            ->whereNull('paid_at')
            ->where('fee_minor', '>', 0)
            ->update(['paid_at' => now(), 'paid_by' => $this->by->id]) === 1;
    }
}
