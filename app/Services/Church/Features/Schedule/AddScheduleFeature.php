<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Schedule;

use App\Domains\Schedule\Jobs\CreateScheduleJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Data\ScheduleInput;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Add a class at the satellite in the address (DECISIONS 2026-10-26, 2026-11-08): a name, weekly on a day or one-time
 * on a date (spec item 14), and a start and an end time. A satellite this admin does not look after is a 404; a name an active class there already has is
 * refused on the name field (the database holds it).
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Add a class',
    persona: 'satellite admin',
    happyPaths: ['story#97' => 'An Admin adds a class with a name, weekly on a day or one-time on a date, and its start and end times'],
    unhappyPaths: ['story#98' => 'Only at a satellite they look after; a name, the day or the date, and both times as HH:MM are needed, the end after the start; no two active classes at a satellite share a name'],
)]
#[AccessRow(AccessRowEnum::ManageClasses)]
final class AddScheduleFeature extends BaseChurchFeature
{
    public function handle(ScheduleInput $input): RedirectResponse
    {
        $scope = $this->scope();
        abort_unless($scope->canManageClasses(), 404);

        $satellite = $this->satellite();
        abort_unless($scope->canManageClassesAt($satellite), 404);

        try {
            $this->run(new CreateScheduleJob(
                satellite: $satellite,
                name: $input->name,
                weekday: $input->weekly(),
                date: $input->oneTimeDate(),
                startsAt: $input->starts_at,
                endsAt: $input->ends_at,
            ));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => "{$satellite->name} already has a class called \"{$input->name}\"."]);
        }

        return back()->with('success', "Added \"{$input->name}\" at {$satellite->name}.");
    }
}
