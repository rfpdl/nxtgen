<?php

declare(strict_types=1);

namespace App\Domains\Card\Jobs;

use App\Data\Models\CardRequest;
use App\Data\Models\User;
use App\Enums\CardMoveEnum;
use App\Enums\CardStepEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * Mark one approved Card request printed, saying who and when.
 *
 * One conditional UPDATE from CardMoveEnum::MarkPrinted's steps — see CardMoveEnum for why, and for what keeps out another
 * church's request.
 *
 * @extends BaseJob<bool>
 */
final class UpdateCardRequestAsPrintedJob extends BaseJob
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
            ->whereIn('step', CardMoveEnum::MarkPrinted->fromStepValues())
            ->update(['step' => CardStepEnum::Printed->value, 'printed_at' => now(), 'printed_by' => $this->by->id]) === 1;
    }
}
