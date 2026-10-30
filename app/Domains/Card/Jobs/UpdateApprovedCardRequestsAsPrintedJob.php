<?php

declare(strict_types=1);

namespace App\Domains\Card\Jobs;

use App\Data\Models\CardRequest;
use App\Data\Models\Church;
use App\Data\Models\User;
use App\Enums\CardMoveEnum;
use App\Enums\CardStepEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * "Mark all printed" on Cards (name cards 03): every Approved Card request of this church whose child's family lives
 * at one of `$satelliteIds` (null = every satellite: a church admin) — the ones the admin sees — printed, saying who
 * and when. One UPDATE from CardMoveEnum::MarkPrinted's steps, so a request another admin moved meanwhile is left alone. Returns
 * how many it marked.
 *
 * @extends BaseJob<int>
 */
final class UpdateApprovedCardRequestsAsPrintedJob extends BaseJob
{
    /** @param list<int>|null $satelliteIds */
    public function __construct(
        private readonly Church $church,
        private readonly ?array $satelliteIds,
        private readonly User $by,
    ) {}

    public function handle(): int
    {
        return CardRequest::query()
            ->where('church_id', $this->church->id)
            ->whereIn('step', CardMoveEnum::MarkPrinted->fromStepValues())
            ->ofFamiliesAt($this->satelliteIds)
            ->update(['step' => CardStepEnum::Printed->value, 'printed_at' => now(), 'printed_by' => $this->by->id]);
    }
}
