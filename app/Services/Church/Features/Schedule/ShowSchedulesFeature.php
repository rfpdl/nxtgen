<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Schedule;

use App\Data\Models\Schedule;
use App\Domains\Church\DataObjects\SatelliteOptionData;
use App\Domains\Church\Jobs\GetSatellitesJob;
use App\Domains\Schedule\DataObjects\SatelliteSchedulesData;
use App\Domains\Schedule\DataObjects\ScheduleData;
use App\Domains\Schedule\Jobs\GetSchedulesBySatelliteJob;
use App\Enums\AccessRowEnum;
use App\Enums\WeekdayEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Features\BaseChurchFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Classes (GLOSSARY "Schedule", shown as "class" — DECISIONS 2026-10-26): each satellite this admin looks after,
 * with its active classes in the order of the week. A church admin sees every satellite; a satellite admin their
 * own; a class admin (TODO #9) only their own class, and adds none. Adding, editing and retiring happen on this page.
 *
 * @extends BaseChurchFeature<Response>
 */
#[UserStory(
    title: 'See a satellite\'s classes',
    persona: 'church admin or satellite admin',
    happyPaths: [
        'story#95' => 'An Admin sees the classes of the satellites they look after; a church admin every satellite, a Satellite admin only their own',
        'story#182' => 'A class admin (TODO #9) sees their own class — and its door, Who\'s in and Attendance — and edits or retires it',
    ],
    unhappyPaths: [
        'story#96' => 'Not there for a Lifeshaper',
        'story#183' => 'A class admin sees nothing of another class or satellite, adds no class, and opens no other Admin page',
    ],
)]
#[AccessRow(AccessRowEnum::ManageClasses)]
final class ShowSchedulesFeature extends BaseChurchFeature
{
    public function handle(): Response
    {
        $church = $this->church();
        $scope = $this->scope();
        abort_unless($scope->canManageClasses(), 404);

        // The satellites this admin looks after, and a class admin's class's satellite (names from the one satellites
        // query), then all their classes in one — of which a class admin sees only their own (TODO #9).
        $ids = $scope->classSatelliteIds();
        $classAdminOf = $scope->classAdminScheduleIds();
        $options = array_values(array_filter(
            $this->run(new GetSatellitesJob(church: $church)),
            fn (SatelliteOptionData $s): bool => $ids === null || in_array($s->id, $ids, true) || in_array($s->id, $scope->adminSchedules, true),
        ));
        $schedules = $this->run(new GetSchedulesBySatelliteJob(church: $church, satelliteIds: array_map(fn (SatelliteOptionData $s): int => $s->id, $options)));

        $satellites = array_map(function (SatelliteOptionData $s) use ($ids, $classAdminOf, $schedules): SatelliteSchedulesData {
            $manages = $ids === null || in_array($s->id, $ids, true);
            $shown = array_values(array_filter($schedules[$s->id] ?? [], fn (Schedule $c): bool => $manages || in_array($c->id, $classAdminOf, true)));

            return new SatelliteSchedulesData(id: $s->id, name: $s->name, schedules: array_map(ScheduleData::fromModel(...), $shown), can_add: $manages);
        }, $options);

        return Inertia::render('Church/Schedules/Index', [
            'satellites' => $satellites,
            'enums' => ['weekday' => WeekdayEnum::toFrontend()],
        ]);
    }
}
