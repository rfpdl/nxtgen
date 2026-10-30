<?php

declare(strict_types=1);

namespace App\Domains\Card\Jobs;

use App\Data\Models\CardRequest;
use App\Data\Models\User;
use App\Enums\CardMoveEnum;
use App\Enums\CardStepEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * Mark a printed card given to the child or parent (at the desk, or from Cards), saying who and when. Given closes
 * the request, so the Child is free for a new one.
 *
 * One conditional UPDATE from CardMoveEnum::Give's steps — see CardMoveEnum for why, and for what keeps out another
 * church's request.
 *
 * @extends BaseJob<bool>
 */
final class UpdateCardRequestAsGivenJob extends BaseJob
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
            ->whereIn('step', CardMoveEnum::Give->fromStepValues())
            ->update(['step' => CardStepEnum::Given->value, 'given_at' => now(), 'given_by' => $this->by->id]) === 1;
    }
}
