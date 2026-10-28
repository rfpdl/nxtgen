<?php

declare(strict_types=1);

use App\Domains\CheckIn\DataObjects\AttendanceChildData;
use App\Domains\CheckIn\DataObjects\AttendanceSquadData;
use App\Domains\CheckIn\DataObjects\SquadHeadCountData;
use App\Domains\CheckIn\Jobs\CreateAttendanceSpreadsheetJob;
use App\Foundation\DataObjects\LevelData;

use function Tests\Support\spreadsheetRows;

/* A Session's Attendance as Excel: the page's rows — a row per child, under its Squad — every cell plain text. */

it('writes the header and the page\'s rows, a row per child with its Squad, Present and the Squad\'s head count', function (): void {
    $squads = [
        new AttendanceSquadData(name: 'Level 1', level: LevelData::of(1), count: 1, children: [new AttendanceChildData(id: 1, nickname: 'Ben', full_name: 'Benjamin Cruz', checked_in_at: '12:58', checked_in_by: 'Liza', present: false)], head_count: null),
        new AttendanceSquadData(name: 'Level 2 Girls', level: LevelData::of(2), count: 2, children: [
            new AttendanceChildData(id: 2, nickname: 'Amara', full_name: 'Amara Joy Cruz', checked_in_at: '13:05', checked_in_by: '', present: true),
            new AttendanceChildData(id: 3, nickname: 'Zoe', full_name: 'Zoe Cruz', checked_in_at: '13:06', checked_in_by: 'Liza', present: false),
        ], head_count: new SquadHeadCountData(count: 3, counted_at: '13:20', counted_by: 'Liza')),
    ];

    expect(spreadsheetRows((new CreateAttendanceSpreadsheetJob(squads: $squads))->handle()))->toBe([
        'Squad|Child nickname|Child full name|Checked in at|Checked in by|Present|Squad head count',
        'Level 1|Ben|Benjamin Cruz|12:58|Liza||',
        'Level 2 Girls|Amara|Amara Joy Cruz|13:05||Yes|3',
        'Level 2 Girls|Zoe|Zoe Cruz|13:06|Liza||3',
    ]);
});

it('writes a nickname that looks like a formula as plain text, never a formula', function (): void {
    $squads = [new AttendanceSquadData(name: 'Level 1', level: LevelData::of(1), count: 1, children: [new AttendanceChildData(id: 1, nickname: '=HYPERLINK("http://evil","x")', full_name: '=1+1', checked_in_at: '12:58', checked_in_by: 'Liza', present: false)], head_count: null)];

    $zip = new ZipArchive;
    $zip->open((new CreateAttendanceSpreadsheetJob(squads: $squads))->handle());
    $sheet = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    expect($sheet)->not->toContain('<f>')->and($sheet)->toContain('=HYPERLINK');
});

it('writes one row for a counted Squad with no one in now: its name and head count, the child cells empty', function (): void {
    $squads = [new AttendanceSquadData(name: 'Level 3 Boys', level: LevelData::of(3), count: 0, children: [], head_count: new SquadHeadCountData(count: 2, counted_at: '13:30', counted_by: 'Liza'))];

    expect(spreadsheetRows((new CreateAttendanceSpreadsheetJob(squads: $squads))->handle()))->toBe([
        'Squad|Child nickname|Child full name|Checked in at|Checked in by|Present|Squad head count',
        'Level 3 Boys||||||2',
    ]);
});
