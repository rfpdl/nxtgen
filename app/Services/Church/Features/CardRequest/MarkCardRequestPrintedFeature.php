<?php

declare(strict_types=1);

namespace App\Services\Church\Features\CardRequest;

use App\Domains\Card\Jobs\UpdateCardRequestAsPrintedJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use Illuminate\Http\RedirectResponse;

/**
 * "Mark printed" on one row of Cards' Approved tab (name cards 03).
 *
 * @extends BaseCardRequestFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Mark a new card printed',
    persona: 'church admin or satellite admin',
    happyPaths: ['story#142' => 'An Admin marks one approved Card request printed'],
    unhappyPaths: ['story#143' => 'Not one that is not approved, and not by a Lifeshaper, another Satellite\'s Admin or a parent'],
)]
#[AccessRow(AccessRowEnum::Cards)]
final class MarkCardRequestPrintedFeature extends BaseCardRequestFeature
{
    public function __construct(private readonly int $requestId) {}

    public function handle(): RedirectResponse
    {
        $request = $this->scopedRequest($this->requestId, $this->scope()->canEditFamily(...));
        $moved = $this->run(new UpdateCardRequestAsPrintedJob(request: $request, by: $this->staff()));

        return $this->answer($moved, $request, "{$request->child->displayName()}'s card is marked printed.");
    }
}
