<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Attendance;

use App\Data\Models\Schedule;
use App\Domains\CheckIn\DataObjects\AttendanceSelectionData;
use App\Domains\CheckIn\DataObjects\AttendanceSquadData;
use App\Domains\CheckIn\Jobs\GetAttendanceSelectionJob;
use App\Domains\CheckIn\Jobs\GetReportableSchedulesJob;
use App\Domains\CheckIn\Jobs\GetSessionAttendanceJob;
use App\Domains\CheckIn\Jobs\GetSessionHeadCountsJob;
use App\Domains\CheckIn\Jobs\GetSessionJob;
use App\Domains\Church\DataObjects\SatelliteOptionData;
use App\Domains\Church\Jobs\GetChurchTodayJob;
use App\Domains\Church\Jobs\GetSatellitesJob;
use App\Services\Church\Features\BaseChurchFeature;

/**
 * What the Attendance page and its Excel share (DECISIONS 2026-10-26: reports are admins only): the satellites this
 * admin sees (a church admin every one, a satellite admin their own) with the classes that can be reported on, the
 * Session `?satellite=`, `?class=` and `?date=` pick (GetAttendanceSelectionJob — a 404 for anything not offered), and
 * its children by Squad with the roll call (each child's present mark, each Squad's head count).
 *
 * @template-covariant TResult
 *
 * @extends BaseChurchFeature<TResult>
 */
abstract class BaseAttendanceFeature extends BaseChurchFeature
{
    /**
     * @return array{
     *     satellites: list<SatelliteOptionData>,
     *     schedules: array<int, list<Schedule>>,
     *     selection: AttendanceSelectionData,
     *     squads: list<AttendanceSquadData>,
     * }
     */
    protected function attendance(): array
    {
        $church = $this->church();
        $scope = $this->scope();
        abort_unless($scope->canSeeAttendance(), 404);

        $ids = $scope->attendanceSatelliteIds();
        $satellites = array_values(array_filter(
            $this->run(new GetSatellitesJob(church: $church)),
            fn (SatelliteOptionData $s): bool => $ids === null || in_array($s->id, $ids, true),
        ));
        $satelliteIds = array_map(fn (SatelliteOptionData $s): int => $s->id, $satellites);
        $schedules = $this->run(new GetReportableSchedulesJob(church: $church, satelliteIds: $satelliteIds));

        $selection = $this->run(new GetAttendanceSelectionJob(
            church: $church,
            satelliteIds: $satelliteIds,
            schedules: $schedules,
            satellite: request()->filled('satellite') ? request()->integer('satellite') : null,
            class: request()->filled('class') ? request()->integer('class') : null,
            date: request()->filled('date') ? request()->string('date')->value() : null,
            today: $this->run(new GetChurchTodayJob(church: $church)),
        ));

        $session = $selection->schedule === null ? null : $this->run(new GetSessionJob(schedule: $selection->schedule, date: $selection->date));

        return [
            'satellites' => $satellites,
            'schedules' => $schedules,
            'selection' => $selection,
            'squads' => $session === null ? [] : AttendanceSquadData::groupsOf(
                $this->run(new GetSessionAttendanceJob(session: $session)),
                $this->run(new GetSessionHeadCountsJob(session: $session)),
                $church->timezone,
            ),
        ];
    }
}
