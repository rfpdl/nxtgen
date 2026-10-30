<?php

declare(strict_types=1);

namespace App\Services\Church\Features\CardRequest;

use App\Data\Models\CardRequest;
use App\Domains\Card\Jobs\CreateCardPrintListSpreadsheetJob;
use App\Enums\AccessRowEnum;
use App\Enums\CardStepEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * "Download" a Print list (name cards 04, Roy 2026-10-29): its own cards the admin may see, as Excel in the shape of
 * the sheet's Barcode tab (CreateCardPrintListSpreadsheetJob) — those still to print or printed (a card given or
 * refused since is left off) — downloadable again any time. Moves no card.
 *
 * @extends BasePrintListFeature<BinaryFileResponse>
 */
#[UserStory(
    title: 'Download a print list',
    persona: 'church admin or satellite admin',
    happyPaths: ['story#148' => 'An Admin downloads a print list\'s cards as Excel — Full name, Nickname, Level, Squad, Barcode (the code the door scans) — again whenever needed, and no card moves'],
    unhappyPaths: ['story#149' => 'Never a card the viewer may not see; another church\'s list, or one with none of theirs, is not found; never for a Lifeshaper or a parent'],
)]
#[AccessRow(AccessRowEnum::Cards)]
final class ExportPrintListFeature extends BasePrintListFeature
{
    /** The cards the sheet lists: still to print, or printed (one may need printing again) — never given or refused. */
    private const array ON_THE_SHEET = [CardStepEnum::Approved, CardStepEnum::Printed];

    public function __construct(private readonly int $printListId) {}

    public function handle(): BinaryFileResponse
    {
        [$list, $cards] = $this->scopedPrintList($this->printListId);
        $toPrint = array_values(array_filter($cards, fn (CardRequest $card): bool => in_array($card->step, self::ON_THE_SHEET, true)));

        $path = $this->run(new CreateCardPrintListSpreadsheetJob(requests: $toPrint));
        $name = "nxtgen-print-list-{$list->id}-".$list->created_at->setTimezone($this->church()->timezone)->toDateString().'.xlsx';

        return response()->download($path, $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }
}
