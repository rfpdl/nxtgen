<?php

declare(strict_types=1);

namespace App\Services\Church\Features\CardRequest;

use App\Data\Models\CardRequest;
use App\Data\Models\PrintList;
use App\Domains\Card\Jobs\GetPrintListCardsJob;
use App\Services\Church\Features\BaseChurchFeature;

/**
 * What a Print list's download and "Mark printed" start from (name cards 04): the list with the id in the URL, in this
 * church, and its cards the admin may see (the address satellite's families) — a 404 for someone with no Cards
 * page, another church's list, or a list holding none of the viewer's cards. A list never shows a card its viewer may
 * not see.
 *
 * @template-covariant TResult
 *
 * @extends BaseChurchFeature<TResult>
 */
abstract class BasePrintListFeature extends BaseChurchFeature
{
    /** @return array{0: PrintList, 1: list<CardRequest>} the list, and its cards the viewer may see */
    protected function scopedPrintList(PrintList $list): array
    {
        $scope = $this->scope();
        abort_unless($scope->canManageCards(), 404);
        $church = $this->church();
        $cards = $this->run(new GetPrintListCardsJob(church: $church, printList: $list, satelliteIds: $scope->cardSatelliteIds()));
        abort_if($cards === [], 404);

        return [$list, $cards];
    }
}
