<?php

declare(strict_types=1);

namespace App\Services\Church\Features\CardRequest;

use App\Domains\Card\Jobs\UpdateCardRequestAsGivenJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use Illuminate\Http\RedirectResponse;

/**
 * "Given" (name cards 03): the printed card reached the child or parent — at the desk, from the child's card on the
 * staff family page (any staff who may open the family), or from Cards' Printed tab. Given closes the request.
 *
 * @extends BaseCardRequestFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Give a new card at the desk',
    persona: 'Lifeshaper or admin',
    happyPaths: ['story#146' => 'Staff who can open the Family mark a printed card given — on the child\'s card or on Cards; the Child may be asked for again'],
    unhappyPaths: ['story#147' => 'Not before it is printed, never twice, and not for a Family they cannot open, nor by a parent'],
)]
#[AccessRow(AccessRowEnum::CardRequest)]
final class GiveCardRequestFeature extends BaseCardRequestFeature
{
    public function __construct(private readonly int $requestId) {}

    public function handle(): RedirectResponse
    {
        $request = $this->scopedRequest($this->requestId, $this->scope()->canSeeFamily(...));
        $moved = $this->run(new UpdateCardRequestAsGivenJob(request: $request, by: $this->staff()));

        return $this->answer($moved, $request, "{$request->child->displayName()}'s new card is given.");
    }
}
