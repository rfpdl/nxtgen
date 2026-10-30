<?php

declare(strict_types=1);

namespace App\Domains\Card\Jobs;

use App\Data\Models\CardRequest;
use App\Data\Models\User;
use App\Enums\CardMoveEnum;
use App\Enums\CardStepEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * Refuse a Card request, with a short reason, saying who and when: before it is printed. Refused closes it, so the
 * Child is free for a new request.
 *
 * One conditional UPDATE from CardMoveEnum::Refuse's steps — see CardMoveEnum for why, and for what keeps out another
 * church's request.
 *
 * @extends BaseJob<bool>
 */
final class UpdateCardRequestAsRefusedJob extends BaseJob
{
    public function __construct(
        private readonly CardRequest $request,
        private readonly string $reason,
        private readonly User $by,
    ) {}

    /** Whether it moved: false = the request was not where this move starts (moved on meanwhile, or never there). */
    public function handle(): bool
    {
        return CardRequest::query()
            ->whereKey($this->request->id)
            ->where('church_id', $this->request->church_id)
            ->whereIn('step', CardMoveEnum::Refuse->fromStepValues())
            ->update(['step' => CardStepEnum::Refused->value, 'refused_reason' => $this->reason, 'refused_at' => now(), 'refused_by' => $this->by->id]) === 1;
    }
}
