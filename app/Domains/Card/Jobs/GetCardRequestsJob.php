<?php

declare(strict_types=1);

namespace App\Domains\Card\Jobs;

use App\Data\Models\CardRequest;
use App\Data\Models\Church;
use App\Enums\CardListTabEnum;
use App\Enums\CardStepEnum;
use App\Foundation\Jobs\BaseJob;
use App\Foundation\Support\ListPage;
use App\Foundation\Support\SearchWords;
use Illuminate\Database\Eloquent\Builder;

/**
 * The Cards page's list (name cards 03): one tab's Card requests whose child's family lives at one of `$satelliteIds`
 * (null = every satellite: a church admin), in this church —
 * and, given a search, only children whose names hold every word. Oldest first while there is something to do (first asked,
 * first done); on the closed tab, the newest filed first. Paged in the database (ListPage), 50 at a time, with each row's child,
 * family, parent and satellite loaded for that page only.
 *
 * @extends BaseJob<ListPage<CardRequest>>
 */
final class GetCardRequestsJob extends BaseJob
{
    /** @param list<int>|null $satelliteIds */
    public function __construct(
        private readonly Church $church,
        private readonly ?array $satelliteIds,
        private readonly CardListTabEnum $tab,
        private readonly int $page,
        private readonly int $perPage = ListPage::PER_PAGE,
        private readonly ?string $query = null,
    ) {}

    /** @return ListPage<CardRequest> */
    public function handle(): ListPage
    {
        $closed = $this->tab === CardListTabEnum::Closed;

        $query = CardRequest::query()
            ->with(['child.family.primaryGuardian', 'child.family.homeSatellite'])
            ->where('church_id', $this->church->id)
            ->whereIn('step', array_map(fn (CardStepEnum $s): string => $s->value, $this->tab->steps()))
            ->ofFamiliesAt($this->satelliteIds)
            // Every word (SearchWords) on the child's own names, as the children list searches them.
            ->when($this->query !== null, fn (Builder $q) => SearchWords::of((string) $this->query)->apply(
                $q,
                fn (Builder $one, string $word) => $one->whereHas('child', fn (Builder $c) => $c
                    ->where('first_name', 'like', SearchWords::like($word))
                    ->orWhere('middle_name', 'like', SearchWords::like($word))
                    ->orWhere('last_name', 'like', SearchWords::like($word))
                    ->orWhere('nickname', 'like', SearchWords::like($word))),
                fn (Builder $one) => $one->whereRaw('1 = 0'),
            ))
            ->orderBy('id', $closed ? 'desc' : 'asc');

        return ListPage::of($query, $this->page, $this->perPage);
    }
}
