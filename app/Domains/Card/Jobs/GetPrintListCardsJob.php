<?php

declare(strict_types=1);

namespace App\Domains\Card\Jobs;

use App\Data\Models\CardRequest;
use App\Data\Models\Church;
use App\Data\Models\PrintList;
use App\Foundation\Jobs\BaseJob;

/**
 * A Print list's cards as the viewer may see them (name cards 04): its own members only, whatever step each is at now,
 * of the families of `$satelliteIds` (null = every satellite) — a list never shows a card its viewer may not see —
 * with each child, in the order filed. What its Excel lists.
 *
 *
 * The church is the acting one (the address's), not the list's: a second guard, should a card of another church ever
 * be on a list.
 *
 * @extends BaseJob<list<CardRequest>>
 */
final class GetPrintListCardsJob extends BaseJob
{
    /** @param list<int>|null $satelliteIds */
    public function __construct(
        private readonly Church $church,
        private readonly PrintList $printList,
        private readonly ?array $satelliteIds,
    ) {}

    /** @return list<CardRequest> */
    public function handle(): array
    {
        return array_values(CardRequest::query()
            ->with('child')
            ->where('church_id', $this->church->id)
            ->whereIn('id', $this->printList->cardIds())
            ->ofFamiliesAt($this->satelliteIds)
            ->orderBy('id')
            ->get()
            ->all());
    }
}
