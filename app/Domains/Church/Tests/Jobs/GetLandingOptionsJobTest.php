<?php

declare(strict_types=1);

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
 * serve in — the first satellite their Roles cover there, in the order satellites were added (there is no church-wide
 * Role — spec item 9).
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

    expect(array_map(fn ($o): array => [$o->church_name, $o->label, $o->url], $options))->toBe([
        ['Alpha Church', 'My family', '/family'],
        ['Alpha Church', 'Serve', '/alpha-zeta'],
        ['Beta Church', 'Serve', '/beta-omega'],
    ]);
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
