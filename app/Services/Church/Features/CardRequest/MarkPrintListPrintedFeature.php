<?php

declare(strict_types=1);

namespace App\Services\Church\Features\CardRequest;

use App\Domains\Card\Jobs\UpdatePrintListAsPrintedJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use Illuminate\Http\RedirectResponse;

/**
 * "Mark these N printed" for a Print list (name cards 04; Astra's fix to 03): its own cards only — those still Approved
 * and within the acting admin's satellites (UpdatePrintListAsPrintedJob). The rest of its cards they may see had
 * already moved on (given, refused, printed), and the message says how many.
 *
 * @extends BasePrintListFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Mark a print list printed',
    persona: 'church admin or satellite admin',
    happyPaths: ['story#144' => 'An Admin marks a print list\'s cards printed — only its own, still approved — and is told how many, and how many had already moved on'],
    unhappyPaths: ['story#145' => 'Never a card that was not on the list, nor another Satellite\'s; a list with none of theirs is not found; never by a Lifeshaper or a parent'],
)]
#[AccessRow(AccessRowEnum::Cards)]
final class MarkPrintListPrintedFeature extends BasePrintListFeature
{
    public function __construct(private readonly int $printListId) {}

    public function handle(): RedirectResponse
    {
        [$list, $cards] = $this->scopedPrintList($this->printListId);

        $marked = $this->run(new UpdatePrintListAsPrintedJob(church: $this->church(), printList: $list, satelliteIds: $this->scope()->cardSatelliteIds(), by: $this->staff()));
        $movedOn = count($cards) - $marked;

        return back()->with('success', "{$marked} marked printed".($movedOn > 0 ? "; {$movedOn} had already moved on." : '.'));
    }
}
