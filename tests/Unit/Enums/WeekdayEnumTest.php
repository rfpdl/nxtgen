<?php

declare(strict_types=1);

use App\Enums\WeekdayEnum;
use Carbon\CarbonImmutable;

/* The day a Schedule (shown as "class") meets each week; a class with no day meets any day. */

it('stores the seven days, Monday first (ISO order)', function (): void {
    expect(array_column(WeekdayEnum::cases(), 'value'))
        ->toBe(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
});

it('numbers each day as ISO 8601 does, so classes sort Monday to Sunday', function (WeekdayEnum $day, int $iso): void {
    expect($day->isoNumber())->toBe($iso);
})->with([
    [WeekdayEnum::Monday, 1],
    [WeekdayEnum::Saturday, 6],
    [WeekdayEnum::Sunday, 7],
]);

it('names each day as people pick it', function (): void {
    expect([WeekdayEnum::Saturday->label(), WeekdayEnum::Saturday->color()])->toBe(['Saturday', 'muted']);
});

it('says "Saturdays" for a class that meets every one of them', function (): void {
    expect(WeekdayEnum::Saturday->everyLabel())->toBe('Saturdays');
});

it('names the day of a date, so the door shows only the classes meeting today', function (string $date, WeekdayEnum $day): void {
    expect(WeekdayEnum::of(CarbonImmutable::parse($date)))->toBe($day);
})->with([
    ['2026-10-31', WeekdayEnum::Saturday],
    ['2026-11-01', WeekdayEnum::Sunday],
    ['2026-10-26', WeekdayEnum::Monday],
]);
