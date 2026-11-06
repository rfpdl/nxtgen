<?php

declare(strict_types=1);

namespace App\Domains\Card\Jobs;

use App\Data\Models\CardRequest;
use App\Data\Models\Church;
use App\Data\Models\PrintList;
use App\Data\Models\User;
use App\Enums\CardMoveEnum;
use App\Enums\CardStepEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * "Mark these N printed" for a Print list (name cards 04; Astra's fix to 03): its own cards only — those still
 * Approved, in its church, of the families of `$satelliteIds` (the acting admin's; null = every satellite) — printed,
 * saying who and when. A card that was never on the list is never touched, however its name, family or approval time
 * changed; one that moved on meanwhile (given, refused, printed by someone else) is skipped. A conditional UPDATE from
 * CardMoveEnum::MarkPrinted's steps (see CardMoveEnum). Returns how many it marked.
 *
 * The cards are found first (a plain read), then updated by id with the step and the satellites checked again (a family moved out meanwhile is left — Astra): an UPDATE that found
 * them itself locked the (church_id, step) index before each row, the opposite order to a single card's move
 * (Refuse locks the row, then that index) — two admins at the same second deadlocked.
 * The church is the acting one (the address's), not the list's: a second guard, should a card of another church ever
 * be on a list.
 *
 * @extends BaseJob<int>
 */
final class UpdatePrintListAsPrintedJob extends BaseJob
{
    /** @param list<int>|null $satelliteIds */
    public function __construct(
        private readonly Church $church,
        private readonly PrintList $printList,
        private readonly ?array $satelliteIds,
        private readonly User $by,
    ) {}

    public function handle(): int
    {
        $ids = CardRequest::query()
            ->where('church_id', $this->church->id)
            ->whereIn('id', $this->printList->cardIds())
            ->whereIn('step', CardMoveEnum::MarkPrinted->fromStepValues())
            ->ofFamiliesAt($this->satelliteIds)
            ->orderBy('id')
            ->pluck('id');

        return CardRequest::query()
            ->whereKey($ids)
            ->where('church_id', $this->church->id)
            ->whereIn('step', CardMoveEnum::MarkPrinted->fromStepValues())
            ->ofFamiliesAt($this->satelliteIds)
            ->update(['step' => CardStepEnum::Printed->value, 'printed_at' => now(), 'printed_by' => $this->by->id]);
    }
}
