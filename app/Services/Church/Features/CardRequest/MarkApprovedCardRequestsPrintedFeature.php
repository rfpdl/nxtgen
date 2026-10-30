<?php

declare(strict_types=1);

namespace App\Services\Church\Features\CardRequest;

use App\Domains\Card\Jobs\UpdateApprovedCardRequestsAsPrintedJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;

/**
 * "Mark all printed" on Cards' Approved tab (name cards 03): every approved card the admin sees — a church admin's
 * every satellite, a satellite admin's own — after the print run. Nothing approved is said plainly, not as an error.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Mark every approved card printed',
    persona: 'church admin or satellite admin',
    happyPaths: ['story#144' => 'An Admin marks every approved card they see printed at once, and is told how many'],
    unhappyPaths: ['story#145' => 'Never another Satellite\'s cards; never by a Lifeshaper or a parent'],
)]
#[AccessRow(AccessRowEnum::Cards)]
final class MarkApprovedCardRequestsPrintedFeature extends BaseChurchFeature
{
    public function handle(): RedirectResponse
    {
        $scope = $this->scope();
        abort_unless($scope->canManageCards(), 404);

        $count = $this->run(new UpdateApprovedCardRequestsAsPrintedJob(church: $this->church(), satelliteIds: $scope->cardSatelliteIds(), by: $this->staff()));

        return back()->with('success', match ($count) {
            0 => 'There were no approved cards to mark printed.',
            1 => 'Marked 1 card printed.',
            default => "Marked {$count} cards printed.",
        });
    }
}
