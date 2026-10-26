<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Schedule;

use App\Domains\Schedule\Jobs\GetScheduleJob;
use App\Domains\Schedule\Jobs\UpdateScheduleJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Data\ScheduleInput;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Edit a class: its name, day and times — never its satellite. Same rules as adding one.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Edit a class',
    persona: 'church admin or satellite admin',
    happyPaths: ['story#99' => 'An Admin changes a class\'s name, day or times'],
    unhappyPaths: ['story#100' => 'Only a class at a satellite they look after, still active; the same field rules and a name no other active class there has'],
)]
#[AccessRow(AccessRowEnum::ManageClasses)]
final class UpdateScheduleFeature extends BaseChurchFeature
{
    public function __construct(private readonly int $scheduleId) {}

    public function handle(ScheduleInput $input): RedirectResponse
    {
        $scope = $this->scope();
        abort_unless($scope->canManageClasses(), 404);

        $schedule = $this->run(new GetScheduleJob(church: $this->church(), id: $this->scheduleId));
        abort_unless($scope->canManageClassesAt($schedule->satellite), 404);

        try {
            $this->run(new UpdateScheduleJob(
                schedule: $schedule,
                name: $input->name,
                weekday: $input->weekday,
                startsAt: $input->starts_at,
                endsAt: $input->ends_at,
            ));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => "{$schedule->satellite->name} already has a class called \"{$input->name}\"."]);
        }

        return back()->with('success', "Saved \"{$input->name}\".");
    }
}
