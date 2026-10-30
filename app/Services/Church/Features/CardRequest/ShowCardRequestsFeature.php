<?php

declare(strict_types=1);

namespace App\Services\Church\Features\CardRequest;

use App\Data\Models\CardRequest;
use App\Data\Models\PrintList;
use App\Domains\Card\DataObjects\CardRequestRowData;
use App\Domains\Card\DataObjects\PrintListData;
use App\Domains\Card\Jobs\GetCardRequestsJob;
use App\Domains\Card\Jobs\GetOpenPrintListsJob;
use App\Enums\AccessRowEnum;
use App\Enums\CardListTabEnum;
use App\Enums\CardMoveEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Data\CardListQuery;
use App\Services\Church\Features\BaseChurchFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Cards page (name cards 03, DECISIONS 2026-10-29): the Card requests of the families an admin looks after — a
 * church admin's every satellite, a satellite admin's own — by tab (`?tab=` asked, approved, printed, closed; an
 * unknown one is Asked), searched by the child's name (`?q=`), 50 a page — and, on
 * Approved, the print lists still to print. Lifeshapers and parents have no Cards page.
 *
 * @extends BaseChurchFeature<Response>
 */
#[UserStory(
    title: 'See the cards to do',
    persona: 'church admin or satellite admin',
    happyPaths: ['story#134' => 'An Admin sees the Card requests of their Satellites\' families by step, or finds a child\'s by name — whose, which family, why, the fee'],
    unhappyPaths: ['story#135' => 'Another Satellite\'s requests never show; a Lifeshaper or a parent has no Cards page'],
)]
#[AccessRow(AccessRowEnum::Cards)]
final class ShowCardRequestsFeature extends BaseChurchFeature
{
    public function handle(): Response
    {
        $scope = $this->scope();
        abort_unless($scope->canManageCards(), 404);
        $church = $this->church();
        $address = CardListQuery::from(request());
        $tab = $address->tab;
        $q = $address->search;

        $page = $this->run(new GetCardRequestsJob(church: $church, satelliteIds: $scope->cardSatelliteIds(), tab: $tab, page: $address->page, query: $q));

        return Inertia::render('Church/Cards/Index', [
            'requests' => array_map(fn (CardRequest $r): CardRequestRowData => CardRequestRowData::fromRequest($r), $page->items),
            'pagination' => $page->meta(),
            'tab' => $tab,
            'q' => $q,
            // The print lists still to print, on Approved (name cards 04).
            'print_lists' => $tab === CardListTabEnum::Approved ? array_map(
                fn (PrintList $l): PrintListData => PrintListData::fromModel($l, $church->timezone),
                $this->run(new GetOpenPrintListsJob(church: $church, satelliteIds: $scope->cardSatelliteIds())),
            ) : [],
            'enums' => ['card_tab' => CardListTabEnum::toFrontend(), 'card_move' => CardMoveEnum::toFrontend()],
        ]);
    }
}
