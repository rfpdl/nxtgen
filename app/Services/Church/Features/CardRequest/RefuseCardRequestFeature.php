<?php

declare(strict_types=1);

namespace App\Services\Church\Features\CardRequest;

use App\Domains\Card\Jobs\UpdateCardRequestAsRefusedJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Data\RefuseCardRequestInput;
use Illuminate\Http\RedirectResponse;

/**
 * "Refuse" on Cards (name cards 03): with a short reason (required), before the card is printed. Refused closes the
 * request, so the Child may have a new one.
 *
 * @extends BaseCardRequestFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Refuse a new card',
    persona: 'church admin or satellite admin',
    happyPaths: ['story#140' => 'An Admin refuses a Card request not yet printed, with a short reason; the Child may be asked for again'],
    unhappyPaths: ['story#141' => 'Never without a reason, never once printed, and not by a Lifeshaper, another Satellite\'s Admin or a parent'],
)]
#[AccessRow(AccessRowEnum::Cards)]
final class RefuseCardRequestFeature extends BaseCardRequestFeature
{
    public function __construct(private readonly int $requestId) {}

    public function handle(RefuseCardRequestInput $input): RedirectResponse
    {
        $request = $this->scopedRequest($this->requestId, $this->scope()->canEditFamily(...));
        $moved = $this->run(new UpdateCardRequestAsRefusedJob(request: $request, reason: $input->reason, by: $this->staff()));

        return $this->answer($moved, $request, "{$request->child->displayName()}'s card is refused.");
    }
}
