<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Attendance;

use App\Domains\CheckIn\DataObjects\AttendanceClassData;
use App\Domains\CheckIn\DataObjects\AttendanceSatelliteData;
use App\Domains\CheckIn\DataObjects\AttendanceSquadData;
use App\Domains\Church\DataObjects\SatelliteOptionData;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Attendance (DECISIONS 2026-10-26: reports are admins only, with Excel): pick a satellite, a class (a retired one
 * too, if it met) and a date, and see the children checked in to that Session — by Squad, each with their names, time
 * and who checked them in; a count per Squad and a total. The choosing is BaseAttendanceFeature's.
 *
 * @extends BaseAttendanceFeature<Response>
 */
#[UserStory(
    title: 'See a Session\'s attendance',
    persona: 'church admin or satellite admin',
    happyPaths: ['story#112' => 'An Admin picks a satellite, class and date (today\'s class or the latest Session first; a retired class that met too) and sees the children checked in by Squad, with who checked them in, a count per Squad and a total'],
    unhappyPaths: ['story#113' => 'Not there for a Lifeshaper; another satellite\'s (for a satellite admin) or another church\'s class, a retired class that never met, or a date that is not one, is a 404'],
)]
#[AccessRow(AccessRowEnum::Attendance)]
final class ShowAttendanceFeature extends BaseAttendanceFeature
{
    public function handle(): Response
    {
        ['satellites' => $satellites, 'schedules' => $schedules, 'selection' => $selection, 'squads' => $squads] = $this->attendance();

        return Inertia::render('Church/Attendance/Index', [
            'satellites' => array_map(fn (SatelliteOptionData $s): AttendanceSatelliteData => new AttendanceSatelliteData(
                id: $s->id,
                name: $s->name,
                classes: array_map(AttendanceClassData::fromModel(...), $schedules[$s->id] ?? []),
            ), $satellites),
            'satellite_id' => $selection->satelliteId,
            'class_id' => $selection->schedule?->id,
            'date' => $selection->date->toDateString(),
            'date_label' => $selection->date->format('D j M'),
            'squads' => $squads,
            'total' => array_sum(array_map(fn (AttendanceSquadData $s): int => $s->count, $squads)),
        ]);
    }
}
