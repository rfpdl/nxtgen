<?php

declare(strict_types=1);

namespace App\Services\Church\Features\CardRequest;

use App\Domains\Card\Jobs\CreatePrintListJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Data\MakePrintListInput;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * "Make print list" on Cards' Approved tab (name cards 04, GLOSSARY "Print list"): the approved cards the tab shows —
 * the admin's satellites (a church admin's every one), the search typed — kept as a list by id, said to be made by
 * them. It then shows on the Approved tab, to download and to mark printed. Nothing approved: said plainly.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Make a print list',
    persona: 'church admin or satellite admin',
    happyPaths: ['story#150' => 'An Admin makes a print list of the approved cards the tab shows — exactly those, kept — and sees it on the Approved tab'],
    unhappyPaths: ['story#151' => 'Nothing approved to show makes no list; never by a Lifeshaper or a parent'],
)]
#[AccessRow(AccessRowEnum::Cards)]
final class MakePrintListFeature extends BaseChurchFeature
{
    public function handle(MakePrintListInput $input): RedirectResponse
    {
        $scope = $this->scope();
        abort_unless($scope->canManageCards(), 404);
        $list = DB::transaction(fn () => $this->run(new CreatePrintListJob(church: $this->church(), satelliteIds: $scope->cardSatelliteIds(), query: $input->search(), by: $this->staff())));
        if ($list === null) {
            return back()->with('success', 'There were no approved cards to put on a print list.');
        }
        $count = (int) $list->cards_count;

        return back()->with('success', 'Print list made: '.($count === 1 ? '1 card.' : "{$count} cards."));
    }
}
