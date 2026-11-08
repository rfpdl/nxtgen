<?php

declare(strict_types=1);

use App\Data\Models\Church;
use App\Data\Models\Satellite;
use App\Data\Models\Schedule;
use App\Domains\CheckIn\DataObjects\AttendanceSelectionData;
use App\Domains\CheckIn\Jobs\GetAttendanceSelectionJob;
use Carbon\CarbonImmutable;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;

/*
 * Which Session Attendance shows, at the satellite in the address (DECISIONS 2026-11-08: no satellite to pick): what
 * the address picks (a class, a date — each checked against the classes offered), or — with nothing picked — today's
 * class with a Session, else the latest Session up to today, else the first class today. Today is Saturday 31 Oct
 * 2026.
 */

/** @return array{church: Church, dxb: Satellite, house: Satellite, onePm: Schedule, sevenPm: Schedule, old: Schedule, tenAm: Schedule} */
function selectionWorld(): array
{
    $church = church();
    $dxb = satelliteOf($church);
    $house = satelliteOf($church);

    return [
        'church' => $church, 'dxb' => $dxb, 'house' => $house,
        'onePm' => scheduleAt($dxb, ['name' => '1PM', 'starts_at' => '13:00']),
        'sevenPm' => scheduleAt($dxb, ['name' => '7PM', 'starts_at' => '19:00']),
        'old' => scheduleAt($dxb, ['name' => 'Old class', 'is_active' => false]),
        'tenAm' => scheduleAt($house, ['name' => '10AM', 'starts_at' => '10:00']),
    ];
}

/**
 * Dxb's Attendance: its three classes offered (or `$schedules`).
 *
 * @param  array{church: Church, dxb: Satellite, house: Satellite, onePm: Schedule, sevenPm: Schedule, old: Schedule, tenAm: Schedule}  $w
 * @param  list<Schedule>|null  $schedules
 */
function selectAttendance(array $w, ?int $class = null, ?string $date = null, ?array $schedules = null): AttendanceSelectionData
{
    return (new GetAttendanceSelectionJob(
        church: $w['church'],
        schedules: $schedules ?? [$w['onePm'], $w['sevenPm'], $w['old']],
        class: $class,
        date: $date,
        today: CarbonImmutable::parse('2026-10-31'),
    ))->handle();
}

/** @return array{0: int|null, 1: string} */
function attendancePicked(AttendanceSelectionData $s): array
{
    return [$s->schedule?->id, $s->date->toDateString()];
}

describe('with nothing picked', function (): void {
    it('opens on a Session today, the earliest class that has one', function (): void {
        $w = selectionWorld();
        sessionOf($w['onePm'], '2026-10-24');
        sessionOf($w['sevenPm'], '2026-10-31');
        sessionOf($w['tenAm'], '2026-10-31');

        expect(attendancePicked(selectAttendance($w)))->toBe([$w['sevenPm']->id, '2026-10-31']);
    });

    it('else opens on the latest Session up to today — a retired class\'s too, never a later day or a class not offered', function (): void {
        $w = selectionWorld();
        sessionOf($w['onePm'], '2026-10-17');
        sessionOf($w['old'], '2026-10-24');
        sessionOf($w['onePm'], '2026-11-07');
        sessionOf($w['tenAm'], '2026-10-30');

        expect(attendancePicked(selectAttendance($w)))->toBe([$w['old']->id, '2026-10-24']);
    });

    it('else opens on the first class today — or none when no class is offered', function (): void {
        $w = selectionWorld();

        expect(attendancePicked(selectAttendance($w)))->toBe([$w['onePm']->id, '2026-10-31'])
            ->and(attendancePicked(selectAttendance($w, schedules: [])))->toBe([null, '2026-10-31']);
    });
});

describe('with something picked', function (): void {
    it('takes a class, a retired class and a date', function (): void {
        $w = selectionWorld();

        expect(attendancePicked(selectAttendance($w, class: $w['sevenPm']->id, date: '2026-10-24')))->toBe([$w['sevenPm']->id, '2026-10-24'])
            ->and(attendancePicked(selectAttendance($w, class: $w['old']->id)))->toBe([$w['old']->id, '2026-10-31'])
            ->and(attendancePicked(selectAttendance($w, date: '2026-10-24')))->toBe([$w['onePm']->id, '2026-10-24']);
    });

    it('refuses a class not offered — another satellite\'s or another church\'s — and a date that is not one', function (string $case): void {
        $w = selectionWorld();
        $theirs = scheduleAt(satelliteOf(church()))->id;

        expect(fn () => match ($case) {
            'elsewhere' => selectAttendance($w, class: $w['tenAm']->id),
            'church' => selectAttendance($w, class: $theirs),
            'format' => selectAttendance($w, date: '31-10-2026'),
            default => selectAttendance($w, date: '2026-02-30'),
        })->toThrow(NotFoundHttpException::class);
    })->with([
        'a class at another satellite' => ['elsewhere'],
        'another church\'s class' => ['church'],
        'a date in another format' => ['format'],
        'a date that does not exist' => ['nonsense'],
    ]);
});
