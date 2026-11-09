<?php

declare(strict_types=1);

use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Domains\Church\Jobs\GetLandingOptionsJob;
use App\Enums\RoleEnum;

use function Tests\Support\atClass;
use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\grantRole;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;

/*
 * Where a login can go: their family page (`/family`) where they are a guardian, and a staff home per church they
 * serve in (spec item 11): the satellite they used last, else the only one their Roles cover, else "Pick your
 * satellite" (`/pick`).
 */

it('offers the family page first, then staff, for each church in name order', function (): void {
    $user = User::factory()->create();
    $beta = church(['name' => 'Beta Church', 'slug' => 'beta']);
    $alpha = church(['name' => 'Alpha Church', 'slug' => 'alpha']);
    $alphaZeta = satelliteOf($alpha, ['name' => 'Zeta', 'slug' => 'alpha-zeta']);
    $alphaDelta = satelliteOf($alpha, ['name' => 'Delta', 'slug' => 'alpha-delta']);
    satelliteOf($beta, ['name' => 'Aardvark', 'slug' => 'beta-aardvark']);
    $omega = satelliteOf($beta, ['name' => 'Omega', 'slug' => 'beta-omega']);
    $alphaHall = satelliteOf($beta, ['name' => 'Alpha Hall', 'slug' => 'beta-alpha-hall']);
    guardianOf(familyAt($alphaDelta), $user);
    grantRole($user, RoleEnum::Lifeshaper, atSatellite($alphaDelta));
    grantRole($user, RoleEnum::Lifeshaper, atSatellite($alphaZeta));
    grantRole($user, RoleEnum::Admin, atSatellite($alphaHall));
    grantRole($user, RoleEnum::Lifeshaper, atSatellite($omega));

    $options = (new GetLandingOptionsJob(user: $user))->handle();

    // Two satellites in each church and none used yet: pick one (spec item 11).
    expect(array_map(fn ($o): array => [$o->church_name, $o->label, $o->url], $options))->toBe([
        ['Alpha Church', 'My family', '/family'],
        ['Alpha Church', 'Serve', '/pick'],
        ['Beta Church', 'Serve', '/pick'],
    ]);
});

describe('the satellite a person lands at (spec item 11)', function (): void {
    it('is the one they used last', function (): void {
        $church = church();
        $dubai = satelliteOf($church, ['slug' => 'dubai']);
        $sharjah = satelliteOf($church, ['slug' => 'sharjah']);
        $user = User::factory()->create();
        grantRole($user, RoleEnum::Admin, atSatellite($dubai));
        grantRole($user, RoleEnum::Lifeshaper, atSatellite($sharjah));
        $user->forceFill(['last_satellite_id' => $sharjah->id])->save();

        expect(array_map(fn ($o): string => $o->url, (new GetLandingOptionsJob(user: $user))->handle()))->toBe(['/sharjah']);
    });

    it('is the only one their Roles cover, when they used none yet', function (): void {
        $church = church();
        $dubai = satelliteOf($church, ['slug' => 'dubai']);
        satelliteOf($church, ['slug' => 'sharjah']);
        $user = User::factory()->create();
        grantRole($user, RoleEnum::Lifeshaper, atSatellite($dubai));

        expect(array_map(fn ($o): string => $o->url, (new GetLandingOptionsJob(user: $user))->handle()))->toBe(['/dubai']);
    });

    it('is the picker when they serve at several and used none — or the last one used is no longer theirs', function (): void {
        $church = church();
        $dubai = satelliteOf($church, ['slug' => 'dubai']);
        $sharjah = satelliteOf($church, ['slug' => 'sharjah']);
        $abuDhabi = satelliteOf($church, ['slug' => 'abu-dhabi']);
        $user = User::factory()->create();
        grantRole($user, RoleEnum::Lifeshaper, atSatellite($dubai));
        grantRole($user, RoleEnum::Lifeshaper, atSatellite($sharjah));

        expect(array_map(fn ($o): string => $o->url, (new GetLandingOptionsJob(user: $user))->handle()))->toBe(['/pick']);

        $user->forceFill(['last_satellite_id' => $abuDhabi->id])->save();
        expect(array_map(fn ($o): string => $o->url, (new GetLandingOptionsJob(user: $user->fresh() ?? $user))->handle()))->toBe(['/pick']);

        // Last used, then its Role taken away: no longer theirs (review, issue 08).
        $user->forceFill(['last_satellite_id' => $sharjah->id])->save();
        RoleAssignment::query()->where('user_id', $user->id)->where('scope_id', $sharjah->id)->update(['is_active' => false]);
        expect(array_map(fn ($o): string => $o->url, (new GetLandingOptionsJob(user: $user->fresh() ?? $user))->handle()))->toBe(['/dubai']);
    });

    it('is any satellite for the platform admin — the one used last, else the picker', function (): void {
        $church = church();
        satelliteOf($church, ['slug' => 'dubai']);
        $sharjah = satelliteOf($church, ['slug' => 'sharjah']);
        $platform = User::factory()->create(['is_platform_admin' => true]);

        expect(array_map(fn ($o): string => $o->url, (new GetLandingOptionsJob(user: $platform))->handle()))->toBe(['/pick']);

        $platform->forceFill(['last_satellite_id' => $sharjah->id])->save();
        expect(array_map(fn ($o): string => $o->url, (new GetLandingOptionsJob(user: $platform))->handle()))->toBe(['/sharjah']);
    });
});

it('takes a class admin to their class\'s satellite (TODO #9)', function (): void {
    $user = User::factory()->create();
    $church = church(['name' => 'Gamma Church']);
    satelliteOf($church, ['name' => 'Alpha', 'slug' => 'gamma-alpha']);
    satelliteOf($church, ['name' => 'Aardvark', 'slug' => 'gamma-aardvark']);
    grantRole($user, RoleEnum::Admin, atClass(scheduleAt(satelliteOf($church, ['name' => 'Beta', 'slug' => 'gamma-beta']))));

    expect(array_map(fn ($o): array => [$o->label, $o->url], (new GetLandingOptionsJob(user: $user))->handle()))
        ->toBe([['Serve', '/gamma-beta']]);
});

it('offers no place for a class admin whose class is retired — that Role gives nothing (review, issue 05)', function (): void {
    $user = User::factory()->create();
    $church = church(['name' => 'Delta Church']);
    grantRole($user, RoleEnum::Admin, atClass(scheduleAt(satelliteOf($church, ['slug' => 'delta-beta']), ['is_active' => false])));

    expect((new GetLandingOptionsJob(user: $user))->handle())->toBe([]);
});

it('offers nothing for a revoked role or for churches the person has no part in', function (): void {
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Admin, atSatellite(satelliteOf(church())), active: false);
    guardianOf(familyAt(satelliteOf(church())), User::factory()->create());
    grantRole(User::factory()->create(), RoleEnum::Admin, atSatellite(satelliteOf(church())));

    expect((new GetLandingOptionsJob(user: $user))->handle())->toBe([]);
});
