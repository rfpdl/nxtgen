<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\Church\Jobs\GetLandingOptionsJob;
use App\Enums\RoleEnum;

use function Tests\Support\atChurch;
use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\grantRole;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;

/* Where a login can go: a family page per church they are a guardian in, a staff page per church they serve in. */

it('offers the family page first, then staff, for each church in name order', function (): void {
    $user = User::factory()->create();
    $beta = church(['name' => 'Beta Church', 'slug' => 'beta']);
    $alpha = church(['name' => 'Alpha Church', 'slug' => 'alpha']);
    guardianOf(familyAt(satelliteOf($alpha)), $user);
    grantRole($user, RoleEnum::Lifeshaper, atChurch($alpha));
    grantRole($user, RoleEnum::Admin, atSatellite(satelliteOf($beta)));

    $options = (new GetLandingOptionsJob(user: $user))->handle();

    expect(array_map(fn ($o): array => [$o->church_name, $o->label, $o->url], $options))->toBe([
        ['Alpha Church', 'My family', '/alpha/family'],
        ['Alpha Church', 'Serve', '/alpha'],
        ['Beta Church', 'Serve', '/beta'],
    ]);
});

it('offers nothing for a revoked role or for churches the person has no part in', function (): void {
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Admin, atChurch(church()), active: false);
    guardianOf(familyAt(satelliteOf(church())), User::factory()->create());
    grantRole(User::factory()->create(), RoleEnum::Admin, atChurch(church()));

    expect((new GetLandingOptionsJob(user: $user))->handle())->toBe([]);
});
