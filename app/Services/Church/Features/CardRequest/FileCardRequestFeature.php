<?php

declare(strict_types=1);

namespace App\Services\Church\Features\CardRequest;

use App\Domains\Card\DataObjects\CardRequestData;
use App\Domains\Card\Jobs\CreateCardRequestJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Data\CardRequestInput;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;

/**
 * "New card…" on a child's card on the staff family page (name cards issue 01, DECISIONS 2026-10-29): staff who may
 * open the family — Lifeshapers and admins — file a Card request, Lost or Damaged, with an optional note. It starts
 * Asked, AED 10, not paid. Parents never file one (they tell a Lifeshaper). While the child has an open request no
 * second one is made: the open one is named instead, in the same words as its Pill.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Ask for a new name card',
    persona: 'Lifeshaper or admin',
    happyPaths: ['story#130' => 'Staff who can open the Family file a lost or damaged card for a Child, with a note; it starts Asked, AED 10, not paid'],
    unhappyPaths: ['story#131' => 'Not for a Family they cannot open, nor by a parent; never a first card by hand; never a second open request — the open one is named'],
)]
#[AccessRow(AccessRowEnum::CardRequest)]
final class FileCardRequestFeature extends BaseChurchFeature
{
    public function __construct(private readonly int $childId) {}

    public function handle(CardRequestInput $input): RedirectResponse
    {
        $child = $this->scopedChild($this->childId, $this->scope()->canSeeFamily(...));

        $request = $this->run(new CreateCardRequestJob(child: $child, reason: $input->reason, note: $input->note, by: $this->staff()));

        if (! $request->wasRecentlyCreated) {
            return back()->with('error', "{$child->displayName()} already has a new card on the way: ".CardRequestData::fromModel($request)->summary.'.');
        }

        return back()->with('success', "A new card for {$child->displayName()} is asked for.");
    }
}
