<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Door;

use App\Data\Models\Session;
use App\Domains\CheckIn\Jobs\UpdateSessionAsClosedJob;
use App\Domains\Church\Jobs\GetChurchTodayJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;

/**
 * An admin closes the Session at the address (spec items 15–16; Roy 2026-11-10: the Session, not "check-in"): from
 * now on it can be looked at, not changed. Already closed — another admin's phone got there first, or midnight — it
 * says so.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Close a Session',
    persona: 'satellite admin, class admin',
    happyPaths: ['story#204' => 'An admin (the satellite\'s, or that class\'s) closes today\'s open Session: from then on its door and Who\'s in are looked at, not changed; its children stay on Attendance'],
    unhappyPaths: ['story#205' => 'Only an admin of its satellite or of that class'],
)]
#[AccessRow(AccessRowEnum::OpenSession)]
final class CloseSessionFeature extends BaseChurchFeature
{
    public function __construct(private readonly Session $session) {}

    public function handle(): RedirectResponse
    {
        $session = $this->session;
        $class = $session->schedule;
        abort_unless($this->scope()->canOpenSessionOf($class), 404);
        $today = $this->run(new GetChurchTodayJob(church: $this->church()));

        if ($session->isOpenOn($today)) {
            $this->run(new UpdateSessionAsClosedJob(session: $session, by: $this->staff()));
        }

        return back()->with('success', "{$class->name} is closed.");
    }
}
