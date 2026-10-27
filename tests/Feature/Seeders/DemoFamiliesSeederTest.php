<?php

declare(strict_types=1);

use App\Data\Models\Child;
use App\Data\Models\Family;
use App\Data\Models\Guardian;
use App\Data\Models\Schedule;
use App\Enums\ChildStatusEnum;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoFamiliesSeeder;

use function Pest\Laravel\seed;

/*
 * Local demo families (Roy 2026-10-27: "a seeder for families and kids so I can test them"). Only `make fresh` on a
 * developer's machine runs it — never production, never the browser tests (their door journeys count on their own
 * families). It gives every case the door and the lists show: each Level and Squad, an allergy, an inactive child,
 * a Graduated one, one without a Level, a visiting House Church family, a second guardian, an unconfirmed email.
 */

it('is part of the local demo, after the demo church', function (): void {
    seed(DatabaseSeeder::class);

    expect(Family::query()->count())->toBeGreaterThanOrEqual(8)
        ->and(Child::query()->count())->toBeGreaterThanOrEqual(15);
});

it('gives the door every case to try', function (): void {
    seed(DatabaseSeeder::class);

    $children = Child::query()->with('family.homeSatellite')->get();

    expect($children->pluck('level')->filter()->unique()->sort()->values()->all())->toBe([1, 2, 3, 4])
        ->and($children->where('status', ChildStatusEnum::Inactive))->not->toBeEmpty()
        ->and($children->where('status', ChildStatusEnum::Graduated))->not->toBeEmpty()
        ->and($children->whereNull('level')->where('status', ChildStatusEnum::Active))->not->toBeEmpty()
        ->and($children->whereNotNull('allergies'))->not->toBeEmpty()
        ->and($children->map(fn (Child $c): string => $c->family->homeSatellite->name)->unique()->sort()->values()->all())->toBe(['CCF DXB', 'House Church'])
        ->and(Guardian::query()->whereNull('user_id')->count())->toBeGreaterThanOrEqual(1);   // a second guardian, no login
});

it('adds a class with no set day, so the door has a class to try on any day', function (): void {
    seed(DatabaseSeeder::class);

    expect(Schedule::query()->whereNull('weekday')->pluck('name')->all())->toBe(['Any day (demo)']);
});

it('is safe to run again', function (): void {
    seed(DatabaseSeeder::class);
    $families = Family::query()->count();

    seed(DemoFamiliesSeeder::class);

    expect(Family::query()->count())->toBe($families)
        ->and(Schedule::query()->whereNull('weekday')->count())->toBe(1);
});
