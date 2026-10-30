<?php

declare(strict_types=1);

namespace App\Services\Church\Features\CardRequest;

use App\Domains\Card\Jobs\UpdateCardRequestAsPaidJob;
use App\Enums\AccessRowEnum;
use App\Enums\CardFeeEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use Illuminate\Http\RedirectResponse;

/**
 * "Mark paid" on Cards (name cards 03): the AED 10 was paid at the desk. Church admin, or admin of the child's family's
 * home satellite. A free (first) card has nothing to pay, and says so; one already paid, or closed, is not
 * marked again — the page says where it is.
 *
 * @extends BaseCardRequestFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Mark a new card paid',
    persona: 'church admin or satellite admin',
    happyPaths: ['story#136' => 'An Admin marks a Card request\'s AED 10 paid (cash at the desk)'],
    unhappyPaths: ['story#137' => 'Not by a Lifeshaper, another Satellite\'s Admin or a parent; never twice; a free card has nothing to pay'],
)]
#[AccessRow(AccessRowEnum::Cards)]
final class MarkCardRequestPaidFeature extends BaseCardRequestFeature
{
    public function __construct(private readonly int $requestId) {}

    public function handle(): RedirectResponse
    {
        $request = $this->scopedRequest($this->requestId, $this->scope()->canEditFamily(...));
        if ($request->fee() === CardFeeEnum::Free) {
            return back()->with('error', "{$request->child->displayName()}'s first card is free — nothing to pay.");
        }
        $moved = $this->run(new UpdateCardRequestAsPaidJob(request: $request, by: $this->staff()));

        return $this->answer($moved, $request, "{$request->child->displayName()}'s card is marked paid.");
    }
}
