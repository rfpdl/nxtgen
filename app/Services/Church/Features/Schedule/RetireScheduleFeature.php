<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Schedule;

use App\Data\Models\Schedule;
use App\Domains\CheckIn\Jobs\GetSessionForUpdateJob;
use App\Domains\CheckIn\Jobs\GetSessionJob;
use App\Domains\CheckIn\Jobs\UpdateSessionAsClosedJob;
use App\Domains\Church\Jobs\GetChurchTodayJob;
use App\Domains\Schedule\Jobs\UpdateScheduleAsInactiveJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Retire a class: it leaves the Classes page and the door, and is kept for the record (never deleted). Its name
 * is free for a new class. Open today, its Session is closed with it — its own address takes no more changes.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Retire a class',
    persona: 'satellite admin',
    happyPaths: ['story#101' => 'An Admin retires a class: it is gone from the page, kept for the record, and its name is free; open today, it closes too'],
    unhappyPaths: ['story#102' => 'Only a class at a satellite they look after, in their church'],
)]
#[AccessRow(AccessRowEnum::ManageClasses)]
final class RetireScheduleFeature extends BaseChurchFeature
{
    public function __construct(private readonly Schedule $schedule) {}

    public function handle(): RedirectResponse
    {
        $scope = $this->scope();
        abort_unless($scope->canManageClasses(), 404);

        $schedule = $this->schedule;
        abort_unless($scope->canManageSchedule($schedule), 404);

        // Retired while open today, its Session closes with it (Astra, Batch C) — under the Session's lock, as every
        // change at its door is, so a check-in at this moment comes before or after, never after it is gone.
        DB::transaction(function () use ($schedule): void {
            $today = $this->run(new GetChurchTodayJob(church: $this->church()));
            $session = $this->run(new GetSessionJob(schedule: $schedule, date: $today));
            if ($session !== null && $this->run(new GetSessionForUpdateJob(session: $session))->isOpenOn($today)) {
                $this->run(new UpdateSessionAsClosedJob(session: $session, by: $this->staff()));
            }
            $this->run(new UpdateScheduleAsInactiveJob(schedule: $schedule));
        });

        return back()->with('success', "Retired \"{$schedule->name}\". It no longer shows at the door.");
    }
}
