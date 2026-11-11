<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Schedule;

use App\Data\Models\Schedule;
use App\Domains\Church\DataObjects\SatelliteOptionData;
use App\Domains\Church\Jobs\GetChurchTodayJob;
use App\Domains\Church\Jobs\GetSatellitesJob;
use App\Domains\Schedule\DataObjects\SatelliteSchedulesData;
use App\Domains\Schedule\DataObjects\ScheduleData;
use App\Domains\Schedule\Jobs\GetSchedulesBySatelliteJob;
use App\Enums\AccessRowEnum;
use App\Enums\WeekdayEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\DataObjects\SchedulesPageData;
use App\Services\Church\Features\BaseChurchFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Classes (GLOSSARY "Schedule", shown as "class" — DECISIONS 2026-10-26): the satellite in the address (DECISIONS
 * 2026-11-08) — one entry in `satellites` — with its active classes in the order of the week, for its admin; a class
 * admin (TODO #9) only their own class, and adds none. A one-time class whose date has gone is on its "Past" list. Adding, editing and retiring happen on this page.
 *
 * @extends BaseChurchFeature<Response>
 */
#[UserStory(
    title: 'See a satellite\'s classes',
    persona: 'satellite admin',
    happyPaths: [
        'story#95' => 'An Admin sees the classes of the satellite in the address — one they look after',
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
            fn (SatelliteOptionData $s): bool => in_array($s->id, $ids, true) || in_array($s->id, $scope->adminSchedules, true),
        ));
        $schedules = $this->run(new GetSchedulesBySatelliteJob(church: $church, satelliteIds: array_map(fn (SatelliteOptionData $s): int => $s->id, $options)));
        $today = $this->run(new GetChurchTodayJob(church: $church));

        $satellites = array_map(function (SatelliteOptionData $s) use ($ids, $classAdminOf, $schedules, $today): SatelliteSchedulesData {
            $manages = in_array($s->id, $ids, true);
            $shown = array_filter($schedules[$s->id] ?? [], fn (Schedule $c): bool => $manages || in_array($c->id, $classAdminOf, true));
            // A one-time class whose date has gone moves to "Past", newest first (Roy 2026-11-10).
            $past = array_reverse(array_filter($shown, fn (Schedule $c): bool => $c->isPastOn($today)));
            $current = array_filter($shown, fn (Schedule $c): bool => ! $c->isPastOn($today));

            return new SatelliteSchedulesData(
                id: $s->id,
                name: $s->name,
                schedules: array_values(array_map(ScheduleData::fromModel(...), $current)),
                past: array_values(array_map(ScheduleData::fromModel(...), $past)),
                can_add: $manages,
            );
        }, $options);

        return Inertia::render('Church/Schedules/Index', new SchedulesPageData(
            satellites: $satellites,
            enums: ['weekday' => WeekdayEnum::toFrontend()],
        ));
    }
}
