<?php

declare(strict_types=1);

namespace App\Domains\Card\Jobs;

use App\Data\Models\CardRequest;
use App\Data\Models\Church;
use App\Data\Models\PrintList;
use App\Enums\CardMoveEnum;
use App\Foundation\Jobs\BaseJob;
use Illuminate\Database\Eloquent\Builder;

/**
 * The Print lists still to print, for Cards' Approved tab (name cards 04): this church's lists holding a card the
 * viewer may still mark printed (Approved, of the families of `$satelliteIds`; null = every satellite), newest first,
 * each counted as the viewer sees it — `cards_count` (its cards they may see) and `to_print_count` (those still
 * Approved) — with who made it.
 *
 * @extends BaseJob<list<PrintList>>
 */
final class GetOpenPrintListsJob extends BaseJob
{
    /** @param list<int>|null $satelliteIds */
    public function __construct(
        private readonly Church $church,
        private readonly ?array $satelliteIds,
    ) {}

    /** @return list<PrintList> */
    public function handle(): array
    {
        return array_values(PrintList::query()
            ->with('maker')
            ->where('church_id', $this->church->id)
            ->withCount([
                'cards' => function (Builder $q): void {
                    /** @var Builder<CardRequest> $q */
                    $q->ofFamiliesAt($this->satelliteIds);
                },
                'cards as to_print_count' => function (Builder $q): void {
                    /** @var Builder<CardRequest> $q */
                    $q->ofFamiliesAt($this->satelliteIds)->whereIn('step', CardMoveEnum::MarkPrinted->fromStepValues());
                },
            ])
            ->whereHas('cards', function (Builder $q): void {
                /** @var Builder<CardRequest> $q */
                $q->ofFamiliesAt($this->satelliteIds)->whereIn('step', CardMoveEnum::MarkPrinted->fromStepValues());
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->all());
    }
}
