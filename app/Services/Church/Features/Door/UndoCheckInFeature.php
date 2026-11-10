<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Door;

use App\Data\Models\CheckIn;
use App\Domains\CheckIn\Jobs\GetSessionForUpdateJob;
use App\Domains\CheckIn\Jobs\UpdateCheckInAsUndoneJob;
use App\Domains\Church\Jobs\GetChurchTodayJob;
use App\Enums\AccessRowEnum;
use App\Enums\SessionStateEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

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
    public function __construct(private readonly CheckIn $checkIn) {}

    public function handle(): RedirectResponse
    {
        $church = $this->church();
        $checkIn = $this->checkIn;
        abort_unless($this->scope()->canCheckInTo($checkIn->session->schedule), 404);
        abort_unless($checkIn->session->date->isSameDay($this->run(new GetChurchTodayJob(church: $church))), 404);

        // A closed Session is looked at, not changed (Roy 2026-11-10): asked under its lock, the date read after it,
        // so an admin closing it at this moment comes before or after the Undo, never between (Astra, issue 11).
        $refusal = DB::transaction(function () use ($church, $checkIn): ?string {
            $session = $this->run(new GetSessionForUpdateJob(session: $checkIn->session));
            $refusal = SessionStateEnum::of($session, $this->run(new GetChurchTodayJob(church: $church)))->refusal($checkIn->session->schedule->name);
            if ($refusal === null && $checkIn->isIn()) {
                $this->run(new UpdateCheckInAsUndoneJob(checkIn: $checkIn, by: $this->staff()));
            }

            return $refusal;
        });
        if ($refusal !== null) {
            return back()->with('error', $refusal);
        }

        return back()->with('success', "Undid {$checkIn->child->displayName()}'s check-in.");
    }
}
