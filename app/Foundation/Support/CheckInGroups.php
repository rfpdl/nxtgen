<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use App\Data\Models\CheckIn;

/** Check-ins grouped by their Squad — the one fold behind Attendance's and Who's in's Squads. */
final class CheckInGroups
{
    /**
     * Keeps the order given (GetSessionAttendanceJob's: by Squad, then nickname).
     *
     * @param  list<CheckIn>  $checkIns
     * @return list<non-empty-list<CheckIn>>
     */
    public static function bySquad(array $checkIns): array
    {
        $groups = [];
        foreach ($checkIns as $checkIn) {
            $groups[$checkIn->squad_id][] = $checkIn;
        }

        return array_values($groups);
    }
}
