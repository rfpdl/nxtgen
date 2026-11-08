<?php

declare(strict_types=1);

use App\Data\Models\Church;
use App\Data\Models\RoleAssignment;
use App\Data\Models\Satellite;
use App\Data\Models\Schedule;
use App\Data\Models\Squad;
use App\Data\Models\User;
use App\Enums\GenderEnum;
use App\Enums\WeekdayEnum;
use Database\Seeders\ChurchSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoChurchSeeder;

use function Pest\Laravel\seed;

/*
 * Production start (D-R2): the real church, its satellites with their Squads, and ONE first church admin — from
 * settings, never from code. Everyone else is added on People; families come from the registry import.
 */

/** @param array<string, string|null> $overrides */
function churchSettings(array $overrides = []): void
{
    config(['nxtgen.church' => array_merge([
        'slug' => 'ccf',
        'name' => 'CCF',
        'timezone' => 'Asia/Dubai',
        'satellites' => 'dubai=CCF Dubai|sharjah=CCF Sharjah',
        'first_admin_email' => 'Roy@Example.com',
        'first_admin_name' => 'Roy',
    ], $overrides)]);
}

it('creates the church, its satellites, each satellite\'s Squads and the first person — the platform admin, on no satellite\'s People (Roy 2026-11-08)', function (): void {
    churchSettings();

    seed(ChurchSeeder::class);

    $church = Church::query()->where('slug', 'ccf')->firstOrFail();
    $admin = User::query()->where('email', 'roy@example.com')->firstOrFail();

    expect($church->name)->toBe('CCF')
        ->and(Satellite::query()->where('church_id', $church->id)->orderBy('name')->pluck('name')->all())->toBe(['CCF Dubai', 'CCF Sharjah'])
        ->and(Satellite::query()->orderBy('slug')->pluck('slug')->all())->toBe(['dubai', 'sharjah'])
        // Each with its own QR code (spec item 7): 8 letters and digits, kept on a second run (below).
        ->and(Satellite::query()->pluck('code')->filter(fn (mixed $c): bool => is_string($c) && preg_match('/^[2-9a-hjkmnp-z]{8}$/', $c) === 1)->count())->toBe(2)
        ->and($admin->password)->toBeNull()
        ->and($admin->is_platform_admin)->toBeTrue()
        ->and(RoleAssignment::query()->count())->toBe(0);
});

it('gives every satellite the chart\'s Squads: Level 1 mixed, Levels 2–4 boys and girls', function (): void {
    churchSettings();

    seed(ChurchSeeder::class);

    foreach (Satellite::query()->get() as $satellite) {
        $squads = Squad::query()->where('satellite_id', $satellite->id)->orderBy('level')->orderBy('gender')->get();

        expect($squads->map(fn (Squad $s): array => [$s->level, $s->gender, $s->name()])->all())->toBe([
            [1, null, 'Level 1'],
            [2, GenderEnum::Boy, 'Level 2 Boys'], [2, GenderEnum::Girl, 'Level 2 Girls'],
            [3, GenderEnum::Boy, 'Level 3 Boys'], [3, GenderEnum::Girl, 'Level 3 Girls'],
            [4, GenderEnum::Boy, 'Level 4 Boys'], [4, GenderEnum::Girl, 'Level 4 Girls'],
        ])->and($squads->pluck('church_id')->unique()->all())->toBe([$satellite->church_id]);
    }
});

it('is safe to run again', function (): void {
    churchSettings();

    seed(ChurchSeeder::class);
    $codes = Satellite::query()->orderBy('id')->pluck('code')->all();
    seed(ChurchSeeder::class);

    expect(Satellite::query()->orderBy('id')->pluck('code')->all())->toBe($codes);

    expect(Church::query()->count())->toBe(1)
        ->and(Satellite::query()->count())->toBe(2)
        ->and(Squad::query()->count())->toBe(14)
        ->and(User::query()->count())->toBe(1)
        ->and(User::query()->sole()->is_platform_admin)->toBeTrue();
});

it('refuses to start without a first admin', function (): void {
    churchSettings(['first_admin_email' => null]);

    expect(fn () => seed(ChurchSeeder::class))->toThrow(RuntimeException::class, 'NXTGEN_FIRST_ADMIN_EMAIL');
    expect(Church::query()->count())->toBe(0);
});

it('refuses a satellite address that clashes with a page of the app, or one without a name', function (string $satellites): void {
    churchSettings(['satellites' => $satellites]);

    expect(fn () => seed(ChurchSeeder::class))->toThrow(RuntimeException::class, 'address=name');
    expect(Church::query()->count())->toBe(0);
})->with(['an app page' => ['login=CCF Login'], 'no name' => ['dubai'], 'not an address' => ['Dubai City=CCF Dubai'], 'shaped like a QR code (Astra)' => ['k7m2q9xa=CCF K']]);

it('never puts the demo accounts into production', function (): void {
    churchSettings();
    config(['mail.default' => 'smtp']);
    app()->detectEnvironment(fn (): string => 'production');

    // Directly, not through `db:seed`, which (rightly) stops to ask "are you sure?" in production.
    app(DatabaseSeeder::class)->setContainer(app())->__invoke();

    expect(User::query()->where('email', 'like', '%@nxtgen.test')->exists())->toBeFalse()
        ->and(User::query()->where('email', 'roy@example.com')->exists())->toBeTrue();
});

it('seeds the demo church locally', function (): void {
    app()->detectEnvironment(fn (): string => 'local');

    seed(DatabaseSeeder::class);

    expect(User::query()->where('email', 'admin@nxtgen.test')->exists())->toBeTrue();
});

// Found in review 3.
it('refuses to start production when emails would only go to the log — the first admin could never sign in', function (string $mailer): void {
    churchSettings();
    config(['mail.default' => $mailer]);
    app()->detectEnvironment(fn (): string => 'production');

    expect(fn () => app(DatabaseSeeder::class)->setContainer(app())->__invoke())->toThrow(RuntimeException::class, 'MAIL_MAILER');
    expect(Church::query()->count())->toBe(0);
})->with(['log', 'array']);

// Found in review 3.
it('adds what is missing on a second run, and changes nothing that exists', function (): void {
    churchSettings();
    seed(ChurchSeeder::class);
    $church = Church::query()->sole();
    $church->forceFill(['timezone' => 'Asia/Manila'])->save();
    User::query()->update(['is_platform_admin' => false]);              // the platform admin was taken off

    churchSettings(['satellites' => 'dubai=CCF Dubai|sharjah=CCF Sharjah|abu-dhabi=CCF Abu Dhabi']);
    seed(ChurchSeeder::class);

    expect($church->fresh()?->timezone)->toBe('Asia/Manila')
        ->and(Squad::query()->count())->toBe(21)           // the new satellite got its own seven
        ->and(User::query()->sole()->is_platform_admin)->toBeFalse()
        ->and(Satellite::query()->pluck('name')->sort()->values()->all())->toBe(['CCF Abu Dhabi', 'CCF Dubai', 'CCF Sharjah']);
});

it('gives the demo church\'s CCF Dubai its Saturday classes, 1PM and 7PM — and is safe to run again', function (): void {
    seed(DemoChurchSeeder::class);
    seed(DemoChurchSeeder::class);

    $classes = Schedule::query()->with('satellite')->orderBy('starts_at')->get();

    expect($classes->map(fn (Schedule $s): array => [$s->satellite->name, $s->name, $s->weekday, $s->startsAt(), $s->endsAt(), $s->is_active])->all())->toBe([
        ['CCF Dubai', '1PM', WeekdayEnum::Saturday, '13:00', '15:00', true],
        ['CCF Dubai', '7PM', WeekdayEnum::Saturday, '19:00', '21:00', true],
    ]);
});

it('makes no classes for the real church: its admins add them on the Classes page', function (): void {
    churchSettings();

    seed(ChurchSeeder::class);

    expect(Schedule::query()->count())->toBe(0);
});
