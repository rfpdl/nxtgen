<?php

declare(strict_types=1);

namespace App\Domains\Card\Jobs;

use App\Data\Models\CardRequest;
use App\Data\Models\User;
use App\Enums\CardMoveEnum;
use App\Enums\CardStepEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * Approve a Card request for printing, saying who and when: from Asked, and only once paid or free (DECISIONS
 * 2026-10-29: "Approve needs paid or free").
 *
 * One conditional UPDATE from CardMoveEnum::Approve's steps — see CardMoveEnum for why, and for what keeps out another
 * church's request.
 *
 * @extends BaseJob<bool>
 */
final class UpdateCardRequestAsApprovedJob extends BaseJob
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
            ->whereIn('step', CardMoveEnum::Approve->fromStepValues())
            ->where(fn ($q) => $q->where('fee_minor', 0)->orWhereNotNull('paid_at'))
            ->update(['step' => CardStepEnum::Approved->value, 'approved_at' => now(), 'approved_by' => $this->by->id]) === 1;
    }
}
