<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Door;

use App\Data\Models\Schedule;
use App\Domains\CheckIn\Jobs\GetSessionJob;
use App\Domains\CheckIn\Jobs\UpdateSessionAsClosedJob;
use App\Domains\Church\Jobs\GetChurchTodayJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;

/**
 * An admin closes today's Session of a class (spec item 15; Roy 2026-11-10: the Session, not "check-in"): from now
 * on it can be looked at, not changed. Already closed — another admin's phone got there first — it says so; a class
 * never opened today is a 404.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Close a Session',
    persona: 'satellite admin, class admin',
    happyPaths: ['story#204' => 'An admin (the satellite\'s, or that class\'s) closes today\'s open Session: from then on its door and Who\'s in are looked at, not changed; its children stay on Attendance'],
    unhappyPaths: ['story#205' => 'Only an admin of its satellite or of that class, and only a Session open today'],
)]
#[AccessRow(AccessRowEnum::OpenSession)]
final class CloseSessionFeature extends BaseChurchFeature
{
    public function __construct(private readonly Schedule $schedule) {}

    public function handle(): RedirectResponse
    {
        $class = $this->schedule;
        abort_unless($this->scope()->canOpenSessionOf($class), 404);
        $today = $this->run(new GetChurchTodayJob(church: $this->church()));
        $session = $this->run(new GetSessionJob(schedule: $class, date: $today));
        abort_unless($session !== null && $session->date->isSameDay($today), 404);

        if ($session->isOpenOn($today)) {
            $this->run(new UpdateSessionAsClosedJob(session: $session, by: $this->staff()));
        }

        return back()->with('success', "{$class->name} is closed.");
    }
}
