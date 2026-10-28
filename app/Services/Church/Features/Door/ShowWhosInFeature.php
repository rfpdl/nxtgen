<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Door;

use App\Data\Models\Schedule;
use App\Domains\CheckIn\DataObjects\DoorClassData;
use App\Domains\CheckIn\DataObjects\WhosInSquadData;
use App\Domains\CheckIn\Jobs\GetSessionAttendanceJob;
use App\Domains\CheckIn\Jobs\GetSessionHeadCountsJob;
use App\Domains\CheckIn\Jobs\GetSessionJob;
use App\Domains\Church\Jobs\GetChurchTodayJob;
use App\Domains\Schedule\Jobs\GetChosenScheduleJob;
use App\Domains\Schedule\Jobs\GetTodaysSchedulesJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Data\DoorQueryInput;
use App\Services\Church\Features\BaseChurchFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Who's in, the door's second tab (DECISIONS 2026-10-28 — roll call): the same class picker, date and people as the
 * door (`?class=`, GetChosenScheduleJob); the children checked in to that class today (not undone), by Squad in the
 * Attendance order, each Squad with how many are marked present; each child's nickname, allergy flag and present
 * mark; each Squad's confirmed head count (who and when). `?squad=` picks one Squad (a Squad with no one in now falls
 * back to All). No photo, no phone.
 *
 * @extends BaseChurchFeature<Response>
 */
#[UserStory(
    title: 'See who is in',
    persona: 'Squad leader (any staff)',
    happyPaths: [
        'story#117' => 'Staff see the children checked in to today\'s class by Squad — Level, then mixed, boys, girls; children by nickname — each Squad with "N of M present", each child with the allergy flag and whether they are present, and can pick one Squad',
    ],
    unhappyPaths: ['story#118' => 'Only today\'s classes at satellites their Role covers — another satellite\'s, another church\'s or another day\'s class is a 404; an undone child is not listed; never a photo, a phone number or a health detail'],
)]
#[AccessRow(AccessRowEnum::CheckIn)]
final class ShowWhosInFeature extends BaseChurchFeature
{
    public function handle(DoorQueryInput $address): Response
    {
        $church = $this->church();
        $today = $this->run(new GetChurchTodayJob(church: $church));
        $classes = $this->run(new GetTodaysSchedulesJob(church: $church, satelliteIds: $this->scope()->doorSatelliteIds(), today: $today));
        $class = $this->run(new GetChosenScheduleJob(classes: $classes, id: $address->class));

        $session = $class === null ? null : $this->run(new GetSessionJob(schedule: $class, date: $today));
        $squads = $session === null ? [] : WhosInSquadData::groupsOf(
            $this->run(new GetSessionAttendanceJob(session: $session)),
            $this->run(new GetSessionHeadCountsJob(session: $session)),
            $church->timezone,
        );

        return Inertia::render('Church/Door/WhosIn', [
            'today' => $today->format('D j M'),
            'classes' => array_map(fn (Schedule $s): DoorClassData => DoorClassData::fromModel($s), $classes),
            'class_id' => $class?->id,
            'squads' => $squads,
            'squad_id' => collect($squads)->contains(fn (WhosInSquadData $s): bool => $s->id === $address->squad) ? $address->squad : null,
        ]);
    }
}
