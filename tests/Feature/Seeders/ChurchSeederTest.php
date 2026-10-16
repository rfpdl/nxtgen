<?php

declare(strict_types=1);

use App\Data\Models\Church;
use App\Data\Models\RoleAssignment;
use App\Data\Models\Satellite;
use App\Data\Models\Squad;
use App\Data\Models\User;
use App\Enums\GenderEnum;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use Database\Seeders\ChurchSeeder;
use Database\Seeders\DatabaseSeeder;

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
        'satellites' => 'CCF DXB|House Church',
        'first_admin_email' => 'Roy@Example.com',
        'first_admin_name' => 'Roy',
    ], $overrides)]);
}

it('creates the church, its satellites, each satellite\'s Squads and the first church admin', function (): void {
    churchSettings();

    seed(ChurchSeeder::class);

    $church = Church::query()->where('slug', 'ccf')->firstOrFail();
    $admin = User::query()->where('email', 'roy@example.com')->firstOrFail();

    expect($church->name)->toBe('CCF')
        ->and(Satellite::query()->where('church_id', $church->id)->orderBy('name')->pluck('name')->all())->toBe(['CCF DXB', 'House Church'])
        ->and($admin->password)->toBeNull()
        ->and($admin->is_platform_admin)->toBeFalse()
        ->and(RoleAssignment::query()->where('user_id', $admin->id)->where('role', RoleEnum::Admin)
            ->where('scope_type', ScopeTypeEnum::Church)->where('scope_id', $church->id)->where('is_active', true)->count())->toBe(1);
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
    seed(ChurchSeeder::class);

    expect(Church::query()->count())->toBe(1)
        ->and(Satellite::query()->count())->toBe(2)
        ->and(Squad::query()->count())->toBe(14)
        ->and(User::query()->count())->toBe(1)
        ->and(RoleAssignment::query()->count())->toBe(1);
});

it('refuses to start without a first admin', function (): void {
    churchSettings(['first_admin_email' => null]);

    expect(fn () => seed(ChurchSeeder::class))->toThrow(RuntimeException::class, 'NXTGEN_FIRST_ADMIN_EMAIL');
    expect(Church::query()->count())->toBe(0);
});

it('refuses a church address that clashes with a page of the app', function (): void {
    churchSettings(['slug' => 'login']);

    expect(fn () => seed(ChurchSeeder::class))->toThrow(RuntimeException::class, 'reserved');
});

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

it('refuses to start production when emails would only go to the log — the first admin could never sign in (review 3)', function (string $mailer): void {
    churchSettings();
    config(['mail.default' => $mailer]);
    app()->detectEnvironment(fn (): string => 'production');

    expect(fn () => app(DatabaseSeeder::class)->setContainer(app())->__invoke())->toThrow(RuntimeException::class, 'MAIL_MAILER');
    expect(Church::query()->count())->toBe(0);
})->with(['log', 'array']);

it('adds what is missing on a second run, and changes nothing that exists (review 3)', function (): void {
    churchSettings();
    seed(ChurchSeeder::class);
    $church = Church::query()->sole();
    $church->forceFill(['timezone' => 'Asia/Manila'])->save();
    RoleAssignment::query()->update(['is_active' => false]);          // the church removed that admin

    churchSettings(['satellites' => 'CCF DXB|House Church|Al Quoz']);
    seed(ChurchSeeder::class);

    expect($church->fresh()?->timezone)->toBe('Asia/Manila')
        ->and(Squad::query()->count())->toBe(21)           // the new satellite got its own seven
        ->and(RoleAssignment::query()->where('is_active', true)->count())->toBe(0)
        ->and(Satellite::query()->pluck('name')->sort()->values()->all())->toBe(['Al Quoz', 'CCF DXB', 'House Church']);
});
