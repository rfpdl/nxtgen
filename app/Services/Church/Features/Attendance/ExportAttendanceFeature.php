<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Attendance;

use App\Domains\CheckIn\Jobs\CreateAttendanceSpreadsheetJob;
use App\Domains\Church\DataObjects\SatelliteOptionData;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The same children the Attendance page shows, as Excel: one row per child in, allowlisted columns, every cell plain
 * text — the page's rows, never rebuilt (CreateAttendanceSpreadsheetJob). Named for the satellite, class and date. The choosing is
 * BaseAttendanceFeature's; with no class to report on, there is nothing to download (404).
 *
 * @extends BaseAttendanceFeature<BinaryFileResponse>
 */
#[UserStory(
    title: 'Download a Session\'s attendance',
    persona: 'church admin or satellite admin',
    happyPaths: [
        'story#114' => 'An Admin downloads the Session\'s children as Excel — the rows the page shows, named for the satellite, class and date',
        'story#125' => 'The Excel carries the roll call: Present ("Yes" or empty) and the Squad\'s head count on each of its rows',
    ],
    unhappyPaths: ['story#115' => 'Not for a Lifeshaper; another satellite\'s (for a satellite admin) or another church\'s class is a 404'],
)]
#[AccessRow(AccessRowEnum::Attendance)]
final class ExportAttendanceFeature extends BaseAttendanceFeature
{
    public function handle(): BinaryFileResponse
    {
        ['satellites' => $satellites, 'selection' => $selection, 'squads' => $squads] = $this->attendance();
        $schedule = $selection->schedule;
        $satellite = array_values(array_filter($satellites, fn (SatelliteOptionData $s): bool => $s->id === $selection->satelliteId))[0] ?? null;
        abort_if($satellite === null || $schedule === null, 404);

        $path = $this->run(new CreateAttendanceSpreadsheetJob(squads: $squads));
        $name = 'nxtgen-attendance-'.Str::slug($satellite->name).'-'.Str::slug($schedule->name).'-'.$selection->date->toDateString().'.xlsx';

        return response()->download($path, $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }
}
