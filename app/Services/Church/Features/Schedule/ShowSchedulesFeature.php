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
 * own. Adding, editing and retiring happen on this page.
 *
 * @extends BaseChurchFeature<Response>
 */
#[UserStory(
    title: 'See a satellite\'s classes',
    persona: 'church admin or satellite admin',
    happyPaths: ['story#95' => 'An Admin sees the classes of the satellites they look after; a church admin every satellite, a Satellite admin only their own'],
    unhappyPaths: ['story#96' => 'Not there for a Lifeshaper'],
)]
#[AccessRow(AccessRowEnum::ManageClasses)]
final class ShowSchedulesFeature extends BaseChurchFeature
{
    public function handle(): Response
    {
        $church = $this->church();
        $scope = $this->scope();
        abort_unless($scope->canManageClasses(), 404);

        // The satellites this admin looks after (names from the one satellites query), then all their classes in one.
        $ids = $scope->classSatelliteIds();
        $options = array_values(array_filter(
            $this->run(new GetSatellitesJob(church: $church)),
            fn (SatelliteOptionData $s): bool => $ids === null || in_array($s->id, $ids, true),
        ));
        $schedules = $this->run(new GetSchedulesBySatelliteJob(church: $church, satelliteIds: array_map(fn (SatelliteOptionData $s): int => $s->id, $options)));

        $satellites = array_map(fn (SatelliteOptionData $s): SatelliteSchedulesData => new SatelliteSchedulesData(
            id: $s->id,
            name: $s->name,
            schedules: array_map(fn (Schedule $schedule): ScheduleData => ScheduleData::fromModel($schedule), $schedules[$s->id] ?? []),
        ), $options);

        return Inertia::render('Church/Schedules/Index', [
            'satellites' => $satellites,
            'enums' => ['weekday' => WeekdayEnum::toFrontend()],
        ]);
    }
}
