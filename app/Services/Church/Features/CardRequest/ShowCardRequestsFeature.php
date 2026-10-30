<?php

declare(strict_types=1);

namespace App\Services\Church\Features\CardRequest;

use App\Data\Models\CardRequest;
use App\Domains\Card\DataObjects\CardRequestRowData;
use App\Domains\Card\Jobs\GetCardRequestsJob;
use App\Enums\AccessRowEnum;
use App\Enums\CardListTabEnum;
use App\Enums\CardMoveEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Features\BaseChurchFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Cards page (name cards 03, DECISIONS 2026-10-29): the Card requests of the families an admin looks after — a
 * church admin's every satellite, a satellite admin's own — by tab (`?tab=` asked, approved, printed, closed; an
 * unknown one is Asked), searched by the child's name (`?q=`), 50 a page. Lifeshapers and parents have no Cards page.
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
        $tab = CardListTabEnum::tryFrom(request()->string('tab')->value()) ?? CardListTabEnum::default();
        $q = request()->string('q')->squish()->value();

        $page = $this->run(new GetCardRequestsJob(church: $this->church(), satelliteIds: $scope->cardSatelliteIds(), tab: $tab, page: request()->integer('page', 1), query: $q === '' ? null : $q));

        return Inertia::render('Church/Cards/Index', [
            'requests' => array_map(fn (CardRequest $r): CardRequestRowData => CardRequestRowData::fromRequest($r), $page->items),
            'pagination' => $page->meta(),
            'tab' => $tab,
            'q' => $q === '' ? null : $q,
            'enums' => ['card_tab' => CardListTabEnum::toFrontend(), 'card_move' => CardMoveEnum::toFrontend()],
        ]);
    }
}
