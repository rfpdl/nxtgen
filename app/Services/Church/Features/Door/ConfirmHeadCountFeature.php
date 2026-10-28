<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Door;

use App\Domains\CheckIn\Jobs\GetSessionJob;
use App\Domains\CheckIn\Jobs\GetSessionSquadJob;
use App\Domains\CheckIn\Jobs\UpdateSquadHeadCountJob;
use App\Domains\Church\Jobs\GetChurchTodayJob;
use App\Domains\Schedule\Jobs\GetScheduleJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Data\HeadCountInput;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;

/**
 * "Confirm head count" under a Squad on Who's in (roll call, DECISIONS 2026-10-28): how many children the Squad leader
 * counted, with who and when — confirming again replaces it. Today's Session of a class at a satellite the viewer's
 * Role covers (the door's people), and a Squad with a child checked in to it. Says nothing on success: the Squad's
 * "✓ Confirmed 13:20 by Joy (4)" line is the answer, and a band at the top would push the list down.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Confirm a Squad\'s head count',
    persona: 'Squad leader (any staff)',
    happyPaths: [
        'story#121' => 'A Squad leader confirms how many children they counted in their Squad today; it shows with when and who, confirming again replaces it, and two leaders confirming at once keep one count',
        'story#122' => 'When the count is not the number checked in now, Who\'s in says so — and names the children not marked present once some are',
    ],
    unhappyPaths: ['story#123' => 'Only today\'s class at a satellite their Role covers, in this church, once it has a Session, for a Squad with a child checked in, and a whole number from 0 to 200'],
)]
#[AccessRow(AccessRowEnum::CheckIn)]
final class ConfirmHeadCountFeature extends BaseChurchFeature
{
    public function __construct(
        private readonly int $scheduleId,
        private readonly int $squadId,
    ) {}

    public function handle(HeadCountInput $input): RedirectResponse
    {
        $church = $this->church();
        $class = $this->run(new GetScheduleJob(church: $church, id: $this->scheduleId));
        abort_unless($this->scope()->canCheckInAt($class->satellite), 404);
        $today = $this->run(new GetChurchTodayJob(church: $church));
        abort_unless($class->meetsOn($today), 404);

        $session = $this->run(new GetSessionJob(schedule: $class, date: $today));
        abort_if($session === null, 404);
        $squad = $this->run(new GetSessionSquadJob(session: $session, id: $this->squadId));

        $this->run(new UpdateSquadHeadCountJob(session: $session, squad: $squad, count: $input->count, by: $this->staff()));

        return back();
    }
}
