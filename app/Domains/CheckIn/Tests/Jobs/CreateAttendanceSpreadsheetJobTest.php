<?php

declare(strict_types=1);

use App\Domains\CheckIn\DataObjects\AttendanceChildData;
use App\Domains\CheckIn\DataObjects\AttendanceSquadData;
use App\Domains\CheckIn\Jobs\CreateAttendanceSpreadsheetJob;
use App\Foundation\DataObjects\LevelData;

use function Tests\Support\spreadsheetRows;

/* A Session's Attendance as Excel: the page's rows — a row per child, under its Squad — every cell plain text. */

it('writes the header and the page\'s rows, a row per child with its Squad', function (): void {
    $squads = [
        new AttendanceSquadData(name: 'Level 1', level: LevelData::of(1), count: 1, children: [new AttendanceChildData(id: 1, nickname: 'Ben', full_name: 'Benjamin Cruz', checked_in_at: '12:58', checked_in_by: 'Liza')]),
        new AttendanceSquadData(name: 'Level 2 Girls', level: LevelData::of(2), count: 1, children: [new AttendanceChildData(id: 2, nickname: 'Amara', full_name: 'Amara Joy Cruz', checked_in_at: '13:05', checked_in_by: '')]),
    ];

    expect(spreadsheetRows((new CreateAttendanceSpreadsheetJob(squads: $squads))->handle()))->toBe([
        'Squad|Child nickname|Child full name|Checked in at|Checked in by',
        'Level 1|Ben|Benjamin Cruz|12:58|Liza',
        'Level 2 Girls|Amara|Amara Joy Cruz|13:05|',
    ]);
});

it('writes a nickname that looks like a formula as plain text, never a formula', function (): void {
    $squads = [new AttendanceSquadData(name: 'Level 1', level: LevelData::of(1), count: 1, children: [new AttendanceChildData(id: 1, nickname: '=HYPERLINK("http://evil","x")', full_name: '=1+1', checked_in_at: '12:58', checked_in_by: 'Liza')])];

    $zip = new ZipArchive;
    $zip->open((new CreateAttendanceSpreadsheetJob(squads: $squads))->handle());
    $sheet = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    expect($sheet)->not->toContain('<f>')->and($sheet)->toContain('=HYPERLINK');
});
