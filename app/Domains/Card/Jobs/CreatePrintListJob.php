<?php

declare(strict_types=1);

namespace App\Domains\Card\Jobs;

use App\Data\Models\CardRequest;
use App\Data\Models\Church;
use App\Data\Models\PrintList;
use App\Data\Models\User;
use App\Enums\CardMoveEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * Make a Print list (GLOSSARY "Print list", name cards 04): the Approved cards the tab shows — this church, the families
 * of `$satelliteIds` (null = every satellite: a church admin), the child-name search — kept by id, saying who made it.
 * From then on the list IS those cards: nothing approved, renamed or moved in later joins it. It comes back with `cards_count` set. Null when there is
 * nothing to put on it. The caller runs it in a transaction (the list and its cards together).
 *
 * @extends BaseJob<PrintList|null>
 */
final class CreatePrintListJob extends BaseJob
{
    /** @param list<int>|null $satelliteIds */
    public function __construct(
        private readonly Church $church,
        private readonly ?array $satelliteIds,
        private readonly ?string $query,
        private readonly User $by,
    ) {}

    public function handle(): ?PrintList
    {
        $ids = CardRequest::query()
            ->where('church_id', $this->church->id)
            ->whereIn('step', CardMoveEnum::MarkPrinted->fromStepValues())
            ->ofFamiliesAt($this->satelliteIds)
            ->forChildNamed($this->query)
            ->orderBy('id')
            ->pluck('id')
            ->all();
        if ($ids === []) {
            return null;
        }

        $list = PrintList::query()->create(['church_id' => $this->church->id, 'made_by' => $this->by->id]);
        $list->cards()->attach($ids);
        $list->setAttribute('cards_count', count($ids));

        return $list;
    }
}
