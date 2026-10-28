<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Door;

use App\Domains\CheckIn\Jobs\GetCheckInJob;
use App\Domains\CheckIn\Jobs\UpdateCheckInPresenceJob;
use App\Domains\Church\Jobs\GetChurchTodayJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Data\MarkPresentInput;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;

/**
 * A tap on Who's in (roll call, DECISIONS 2026-10-28): mark a checked-in child present in their Squad, or unmark
 * them — latest wins, with who and when. The same people and the same day as Undo: today's Check-ins, at a satellite
 * the viewer's Role covers. A child undone at the door meanwhile (the list refreshes only every ~20 s) is refused
 * with the reason, not marked; so is a tap on a Check-in undone and revived since (its revision moved on — roll call
 * 05). Marking says nothing: the tick is the answer.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Mark a child present',
    persona: 'Squad leader (any staff)',
    happyPaths: ['story#119' => 'A Squad leader taps a checked-in child present, and taps again to unmark them; only the latest mark is kept, with who and when'],
    unhappyPaths: ['story#120' => 'Only today\'s, at a satellite their Role covers, in this church; a child whose Check-in was undone is refused'],
)]
#[AccessRow(AccessRowEnum::CheckIn)]
final class MarkPresentFeature extends BaseChurchFeature
{
    public function __construct(private readonly int $checkInId) {}

    public function handle(MarkPresentInput $input): RedirectResponse
    {
        $church = $this->church();
        $checkIn = $this->run(new GetCheckInJob(church: $church, id: $this->checkInId));
        abort_unless($this->scope()->canCheckInAt($checkIn->session->satellite), 404);
        abort_unless($checkIn->session->date->isSameDay($this->run(new GetChurchTodayJob(church: $church))), 404);

        if (! $this->run(new UpdateCheckInPresenceJob(checkIn: $checkIn, revision: $input->revision, present: $input->present, by: $this->staff()))) {
            $name = $checkIn->child->displayName();

            // In, but not the Check-in that was tapped: undone and checked in again since the page last showed it.
            return back()->with('error', $checkIn->isIn() && $checkIn->revision !== $input->revision
                ? "{$name} was checked in again — please tap again."
                : "{$name} is not checked in any more.");
        }

        // No success message: the tick says it, and a band appearing at the top would push the list under the next tap.
        return back();
    }
}
