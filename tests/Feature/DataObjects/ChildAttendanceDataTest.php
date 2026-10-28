<?php

declare(strict_types=1);

use App\Data\Models\CheckIn;
use App\Data\Models\Child;
use App\Data\Models\Satellite;
use App\Data\Models\Schedule;
use App\Data\Models\Session;
use App\Data\Models\Squad;
use App\Domains\CheckIn\DataObjects\ChildAttendanceData;
use App\Domains\CheckIn\DataObjects\ChildCheckInData;
use App\Enums\GenderEnum;
use Carbon\CarbonImmutable;

/* A child's attendance lines on the staff family page: one entry per child, each line in the order given. */

function attendanceLine(int $id, int $childId, string $date, bool $present = false): CheckIn
{
    $session = (new Session)->forceFill(['date' => CarbonImmutable::parse($date)])
        ->setRelation('schedule', (new Schedule)->forceFill(['name' => '1PM']))
        ->setRelation('satellite', (new Satellite)->forceFill(['name' => 'CCF DXB']));

    return (new CheckIn)->forceFill(['id' => $id, 'child_id' => $childId, 'present_at' => $present ? CarbonImmutable::now() : null])
        ->setRelation('session', $session)
        ->setRelation('squad', (new Squad)->forceFill(['level' => 2, 'gender' => GenderEnum::Girl]));
}

it('gives every child an entry in the order given — their lines in the order given, none for a child never in', function (): void {
    $amara = (new Child)->forceFill(['id' => 1]);
    $ben = (new Child)->forceFill(['id' => 2]);

    $all = ChildAttendanceData::forChildren(
        [$ben, $amara],
        [attendanceLine(30, 1, '2026-10-31', present: true), attendanceLine(20, 1, '2026-10-24')],
        2026,
    );

    expect(array_map(fn (ChildAttendanceData $a): array => [$a->child_id, array_map(fn (ChildCheckInData $c): int => $c->id, $a->check_ins)], $all))
        ->toBe([[2, []], [1, [30, 20]]])
        ->and($all[1]->check_ins[0]->toArray())->toBe(['id' => 30, 'date' => 'Sat 31 Oct', 'class' => '1PM', 'satellite' => 'CCF DXB', 'squad' => 'Level 2 Girls', 'present' => true]);
});

it('writes the year only when the line is not from this year', function (int $thisYear, string $date): void {
    expect(ChildCheckInData::fromCheckIn(attendanceLine(1, 1, '2026-10-31'), $thisYear)->date)->toBe($date);
})->with([
    'this year' => [2026, 'Sat 31 Oct'],
    'a later year' => [2027, 'Sat 31 Oct 2026'],
]);
