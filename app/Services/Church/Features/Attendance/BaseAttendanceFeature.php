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
use App\Domains\Church\Jobs\GetChurchTodayJob;
use App\Services\Church\Features\BaseChurchFeature;

/**
 * What the Attendance page and its Excel share (DECISIONS 2026-10-26: reports are admins only): the classes of the
 * satellite in the address (DECISIONS 2026-11-08) that this admin may report on, the Session `?class=` and `?date=`
 * pick (GetAttendanceSelectionJob — a 404 for anything not offered), and its children by Squad with the roll call
 * (each child's present mark, each Squad's head count).
 *
 * @template-covariant TResult
 *
 * @extends BaseChurchFeature<TResult>
 */
abstract class BaseAttendanceFeature extends BaseChurchFeature
{
    /**
     * @return array{
     *     schedules: list<Schedule>,
     *     selection: AttendanceSelectionData,
     *     squads: list<AttendanceSquadData>,
     * }
     */
    protected function attendance(): array
    {
        $church = $this->church();
        $scope = $this->scope();
        abort_unless($scope->canSeeAttendance(), 404);

        $satellite = $this->satellite();
        // A class admin (TODO #9) sees only their class's Sessions; an admin of the satellite, all of them.
        $schedules = array_values(array_filter(
            $this->run(new GetReportableSchedulesJob(church: $church, satelliteIds: [$satellite->id]))[$satellite->id] ?? [],
            fn (Schedule $c): bool => $scope->canSeeAttendanceOf($c),
        ));

        $selection = $this->run(new GetAttendanceSelectionJob(
            church: $church,
            schedules: $schedules,
            class: request()->filled('class') ? request()->integer('class') : null,
            date: request()->filled('date') ? request()->string('date')->value() : null,
            today: $this->run(new GetChurchTodayJob(church: $church)),
        ));

        $session = $selection->schedule === null ? null : $this->run(new GetSessionJob(schedule: $selection->schedule, date: $selection->date));

        return [
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
