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
 * Which Session Attendance shows: what the address picks (each part checked against what the admin sees), or — with
 * nothing picked — today's class with a Session, else the latest Session up to today at the first satellite, else
 * the first satellite's first class today. Today is Saturday 31 Oct 2026.
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
 * @param  array{church: Church, dxb: Satellite, house: Satellite, onePm: Schedule, sevenPm: Schedule, old: Schedule, tenAm: Schedule}  $w
 * @param  list<int>|null  $satelliteIds
 */
function selectAttendance(array $w, ?int $satellite = null, ?int $class = null, ?string $date = null, ?array $satelliteIds = null): AttendanceSelectionData
{
    return (new GetAttendanceSelectionJob(
        church: $w['church'],
        satelliteIds: $satelliteIds ?? [$w['dxb']->id, $w['house']->id],
        schedules: [$w['dxb']->id => [$w['onePm'], $w['sevenPm'], $w['old']], $w['house']->id => [$w['tenAm']]],
        satellite: $satellite,
        class: $class,
        date: $date,
        today: CarbonImmutable::parse('2026-10-31'),
    ))->handle();
}

/** @return array{0: int|null, 1: int|null, 2: string} */
function attendancePicked(AttendanceSelectionData $s): array
{
    return [$s->satelliteId, $s->schedule?->id, $s->date->toDateString()];
}

describe('with nothing picked', function (): void {
    it('opens on a Session today, the earliest class at the first satellite that has one', function (): void {
        $w = selectionWorld();
        sessionOf($w['sevenPm'], '2026-10-24');
        sessionOf($w['tenAm'], '2026-10-31');

        expect(attendancePicked(selectAttendance($w)))->toBe([$w['house']->id, $w['tenAm']->id, '2026-10-31']);
    });

    it('else opens on the latest Session up to today at the first satellite — a retired class\'s too, never a later day or another satellite\'s', function (): void {
        $w = selectionWorld();
        sessionOf($w['onePm'], '2026-10-17');
        sessionOf($w['old'], '2026-10-24');
        sessionOf($w['onePm'], '2026-11-07');
        sessionOf($w['tenAm'], '2026-10-30');

        expect(attendancePicked(selectAttendance($w)))->toBe([$w['dxb']->id, $w['old']->id, '2026-10-24']);
    });

    it('else opens on the first satellite\'s first class today', function (): void {
        $w = selectionWorld();

        expect(attendancePicked(selectAttendance($w)))->toBe([$w['dxb']->id, $w['onePm']->id, '2026-10-31'])
            ->and(attendancePicked(selectAttendance($w, satelliteIds: [])))->toBe([null, null, '2026-10-31']);
    });
});

describe('with something picked', function (): void {
    it('takes a satellite (its first class), a class (its satellite), a retired class and a date', function (): void {
        $w = selectionWorld();

        expect(attendancePicked(selectAttendance($w, satellite: $w['house']->id)))->toBe([$w['house']->id, $w['tenAm']->id, '2026-10-31'])
            ->and(attendancePicked(selectAttendance($w, class: $w['sevenPm']->id, date: '2026-10-24')))->toBe([$w['dxb']->id, $w['sevenPm']->id, '2026-10-24'])
            ->and(attendancePicked(selectAttendance($w, satellite: $w['dxb']->id, class: $w['old']->id)))->toBe([$w['dxb']->id, $w['old']->id, '2026-10-31'])
            ->and(attendancePicked(selectAttendance($w, date: '2026-10-24')))->toBe([$w['dxb']->id, $w['onePm']->id, '2026-10-24']);
    });

    it('refuses a satellite or class not offered, a class not at the picked satellite, and a date that is not one', function (string $case): void {
        $w = selectionWorld();
        $stranger = satelliteOf($w['church'])->id;
        $theirs = scheduleAt(satelliteOf(church()))->id;

        expect(fn () => match ($case) {
            'satellite' => selectAttendance($w, satellite: $stranger),
            'class' => selectAttendance($w, class: $theirs),
            'elsewhere' => selectAttendance($w, satellite: $w['dxb']->id, class: $w['tenAm']->id),
            'format' => selectAttendance($w, date: '31-10-2026'),
            default => selectAttendance($w, date: '2026-02-30'),
        })->toThrow(NotFoundHttpException::class);
    })->with([
        'a satellite not offered' => ['satellite'],
        'a class not offered' => ['class'],
        'a class at another satellite' => ['elsewhere'],
        'a date in another format' => ['format'],
        'a date that does not exist' => ['nonsense'],
    ]);
});
