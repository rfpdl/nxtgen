<?php

declare(strict_types=1);

namespace App\Services\Church\Features\CardRequest;

use App\Domains\Card\Jobs\UpdateCardRequestAsApprovedJob;
use App\Enums\AccessRowEnum;
use App\Enums\CardFeeEnum;
use App\Enums\CardStepEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Foundation\Support\Money;
use Illuminate\Http\RedirectResponse;

/**
 * "Approve" on Cards (name cards 03): the card may be printed. Only once paid or free (DECISIONS 2026-10-29) — an
 * unpaid one is refused with what to do; only from Asked, once, even when two admins tap at once.
 *
 * @extends BaseCardRequestFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Approve a new card',
    persona: 'church admin or satellite admin',
    happyPaths: ['story#138' => 'An Admin approves an Asked Card request that is paid or free, for printing'],
    unhappyPaths: ['story#139' => 'Not unpaid (it says to mark it paid first), not twice, and not by a Lifeshaper, another Satellite\'s Admin or a parent'],
)]
#[AccessRow(AccessRowEnum::Cards)]
final class ApproveCardRequestFeature extends BaseCardRequestFeature
{
    public function __construct(private readonly int $requestId) {}

    public function handle(): RedirectResponse
    {
        $request = $this->scopedRequest($this->requestId, $this->scope()->canEditFamily(...));
        $name = $request->child->displayName();
        if ($request->step === CardStepEnum::Asked && $request->fee() === CardFeeEnum::NotPaid) {
            return back()->with('error', "{$name}'s card is not paid yet: mark the ".Money::aed($request->fee_minor).' paid first.');
        }
        $moved = $this->run(new UpdateCardRequestAsApprovedJob(request: $request, by: $this->staff()));

        return $this->answer($moved, $request, "{$name}'s card is approved for printing.");
    }
}
