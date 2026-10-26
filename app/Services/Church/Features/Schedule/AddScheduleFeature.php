<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Schedule;

use App\Domains\Church\Jobs\GetSatelliteJob;
use App\Domains\Schedule\Jobs\CreateScheduleJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Data\AddScheduleInput;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Add a class at a satellite (DECISIONS 2026-10-26): a name, an optional day and start/end time. A satellite this
 * admin does not look after — or another church's — is a 404; a name an active class there already has is
 * refused on the name field (the database holds it).
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Add a class',
    persona: 'church admin or satellite admin',
    happyPaths: ['story#97' => 'An Admin adds a class with a name, and a day and times if it has them'],
    unhappyPaths: ['story#98' => 'Only at a satellite they look after; a name is needed, times are HH:MM and the end comes after the start; no two active classes at a satellite share a name'],
)]
#[AccessRow(AccessRowEnum::ManageClasses)]
final class AddScheduleFeature extends BaseChurchFeature
{
    public function handle(AddScheduleInput $input): RedirectResponse
    {
        $church = $this->church();
        $scope = $this->scope();
        abort_unless($scope->canManageClasses(), 404);

        $satellite = $this->run(new GetSatelliteJob(church: $church, id: $input->satellite_id));
        abort_unless($scope->canManageClassesAt($satellite), 404);

        try {
            $this->run(new CreateScheduleJob(
                satellite: $satellite,
                name: $input->name,
                weekday: $input->weekday,
                startsAt: $input->starts_at,
                endsAt: $input->ends_at,
            ));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => "{$satellite->name} already has a class called \"{$input->name}\"."]);
        }

        return back()->with('success', "Added \"{$input->name}\" at {$satellite->name}.");
    }
}
