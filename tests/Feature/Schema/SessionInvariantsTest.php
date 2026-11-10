<?php

declare(strict_types=1);

use App\Enums\SessionStateEnum;
use Carbon\CarbonImmutable;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;

/*
 * A Session is open from when an admin opens it until it is closed — by an admin, or at midnight at the satellite
 * (spec item 15): the church's date has moved on, so no job is needed. `isOpenOn` is asked with the church's today.
 */

it('is open on its date until it is closed', function (): void {
    $session = sessionOf(scheduleAt(satelliteOf(church())), '2026-10-31');

    expect($session->isOpenOn(CarbonImmutable::parse('2026-10-31')))->toBeTrue();

    $session->update(['closed_at' => now()]);
    expect($session->isOpenOn(CarbonImmutable::parse('2026-10-31')))->toBeFalse();
});

it('is closed after midnight — the day after, and any other day', function (): void {
    $session = sessionOf(scheduleAt(satelliteOf(church())), '2026-10-31');

    expect($session->isOpenOn(CarbonImmutable::parse('2026-11-01')))->toBeFalse()
        ->and($session->isOpenOn(CarbonImmutable::parse('2026-10-30')))->toBeFalse()
        ->and($session->isOpenOn(CarbonImmutable::parse('2026-11-07')))->toBeFalse();
});

it('reads where today\'s Session stands: none is not open yet; open; closed, or of another day, is closed', function (): void {
    $saturday = CarbonImmutable::parse('2026-10-31');
    $session = sessionOf(scheduleAt(satelliteOf(church())), '2026-10-31');

    expect(SessionStateEnum::of(null, $saturday))->toBe(SessionStateEnum::NotOpen)
        ->and(SessionStateEnum::of($session, $saturday))->toBe(SessionStateEnum::Open)
        ->and(SessionStateEnum::of($session, $saturday->addDay()))->toBe(SessionStateEnum::Closed);

    $session->update(['closed_at' => now()]);
    expect(SessionStateEnum::of($session, $saturday))->toBe(SessionStateEnum::Closed);
});
