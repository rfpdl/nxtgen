<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Schedule;

use App\Domains\Schedule\Jobs\GetScheduleJob;
use App\Domains\Schedule\Jobs\UpdateScheduleAsInactiveJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;

/**
 * Retire a class: it leaves the Classes page and the door, and is kept for the record (never deleted). Its name
 * is free for a new class.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Retire a class',
    persona: 'church admin or satellite admin',
    happyPaths: ['story#101' => 'An Admin retires a class: it is gone from the page, kept for the record, and its name is free'],
    unhappyPaths: ['story#102' => 'Only a class at a satellite they look after, in their church'],
)]
#[AccessRow(AccessRowEnum::ManageClasses)]
final class RetireScheduleFeature extends BaseChurchFeature
{
    public function __construct(private readonly int $scheduleId) {}

    public function handle(): RedirectResponse
    {
        $scope = $this->scope();
        abort_unless($scope->canManageClasses(), 404);

        $schedule = $this->run(new GetScheduleJob(church: $this->church(), id: $this->scheduleId));
        abort_unless($scope->canManageClassesAt($schedule->satellite), 404);

        $this->run(new UpdateScheduleAsInactiveJob(schedule: $schedule));

        return back()->with('success', "Retired \"{$schedule->name}\". It no longer shows at the door.");
    }
}
