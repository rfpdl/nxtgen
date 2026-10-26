<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Door;

use App\Domains\CheckIn\Jobs\GetCheckInJob;
use App\Domains\CheckIn\Jobs\UpdateCheckInAsUndoneJob;
use App\Domains\Church\Jobs\GetChurchTodayJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;

/**
 * Undo a Check-in at the door (a child ticked by mistake): the row is kept and stamped, and the child can be checked
 * in again. Only today's, at a satellite the viewer's Role covers.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Undo a Check-in',
    persona: 'Lifeshaper (any staff)',
    happyPaths: ['story#110' => 'Staff undo a Check-in made by mistake; the child can be checked in again'],
    unhappyPaths: ['story#111' => 'Only today\'s, at a satellite their Role covers, in this church'],
)]
#[AccessRow(AccessRowEnum::CheckIn)]
final class UndoCheckInFeature extends BaseChurchFeature
{
    public function __construct(private readonly int $checkInId) {}

    public function handle(): RedirectResponse
    {
        $church = $this->church();
        $checkIn = $this->run(new GetCheckInJob(church: $church, id: $this->checkInId));
        abort_unless($this->scope()->canCheckInAt($checkIn->session->satellite), 404);
        abort_unless($checkIn->session->date->isSameDay($this->run(new GetChurchTodayJob(church: $church))), 404);

        if ($checkIn->isIn()) {
            $this->run(new UpdateCheckInAsUndoneJob(checkIn: $checkIn, by: $this->staff()));
        }

        return back()->with('success', "Undid {$checkIn->child->displayName()}'s check-in.");
    }
}
