<?php

declare(strict_types=1);

use App\Data\Models\RoleAssignment;
use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Domains\Church\Jobs\GetAccessScopeJob;
use App\Domains\Church\Jobs\GetOpenSatellitesJob;
use App\Enums\RoleEnum;

use function Tests\Support\atClass;
use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\grantRole;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;

/*
 * The satellites a person may open, in the order added (spec items 9–12) — "Pick your satellite" and the bar's switch:
 * those an active Role covers (at the satellite or one of its active classes); every satellite for the platform admin.
 */

it('lists the satellites a Role covers, at the satellite or a class, in the order added', function (): void {
    $church = church();
    $dubai = satelliteOf($church);
    $sharjah = satelliteOf($church);
    $abuDhabi = satelliteOf($church);
    $other = satelliteOf($church);
    $user = User::factory()->create();
    grantRole($user, RoleEnum::Lifeshaper, atSatellite($abuDhabi));
    grantRole($user, RoleEnum::Admin, atClass(scheduleAt($dubai)));
    grantRole($user, RoleEnum::Admin, atSatellite($other), active: false);
    grantRole($user, RoleEnum::Admin, atClass(scheduleAt($sharjah, ['is_active' => false])));

    $open = (new GetOpenSatellitesJob(user: $user))->handle();

    expect(array_map(fn (Satellite $s): int => $s->id, $open))->toBe([$dubai->id, $abuDhabi->id]);
});

it('lists every satellite for the platform admin, and none for someone with no Role', function (): void {
    $church = church();
    $dubai = satelliteOf($church);
    $sharjah = satelliteOf($church);

    expect(array_map(fn (Satellite $s): int => $s->id, (new GetOpenSatellitesJob(user: User::factory()->create(['is_platform_admin' => true])))->handle()))
        ->toBe([$dubai->id, $sharjah->id])
        ->and((new GetOpenSatellitesJob(user: User::factory()->create()))->handle())->toBe([]);
});

// Review, issue 08: "the satellites a person may open" is ONE answer — it agrees with the access check at every
// satellite, whatever rows the person holds (made by hand here, as an import might).
it('offers exactly the satellites the access check lets them work at', function (): void {
    $church = church();
    $dubai = satelliteOf($church);
    $sharjah = satelliteOf($church);
    $elsewhere = satelliteOf(church());
    $row = fn (User $u, RoleEnum $role, string $type, int $id, int $churchId, bool $active = true) => RoleAssignment::query()->create([
        'church_id' => $churchId, 'user_id' => $u->id, 'role' => $role, 'scope_type' => $type, 'scope_id' => $id, 'is_active' => $active,
    ]);
    $people = [
        'satellite admin' => fn (User $u) => $row($u, RoleEnum::Admin, 'satellite', $dubai->id, $church->id),
        'satellite Lifeshaper' => fn (User $u) => $row($u, RoleEnum::Lifeshaper, 'satellite', $sharjah->id, $church->id),
        'revoked' => fn (User $u) => $row($u, RoleEnum::Admin, 'satellite', $dubai->id, $church->id, active: false),
        'class admin' => fn (User $u) => $row($u, RoleEnum::Admin, 'schedule', scheduleAt($dubai)->id, $church->id),
        'Lifeshaper at a class' => fn (User $u) => $row($u, RoleEnum::Lifeshaper, 'schedule', scheduleAt($dubai)->id, $church->id),
        'retired class admin' => fn (User $u) => $row($u, RoleEnum::Admin, 'schedule', scheduleAt($sharjah, ['is_active' => false])->id, $church->id),
        'row in the wrong church' => fn (User $u) => $row($u, RoleEnum::Admin, 'satellite', $dubai->id, $elsewhere->church_id),
        'platform admin' => fn (User $u) => $u->forceFill(['is_platform_admin' => true])->save(),
    ];

    foreach ($people as $who => $give) {
        $user = User::factory()->create();
        $give($user);
        $open = array_map(fn (Satellite $s): int => $s->id, (new GetOpenSatellitesJob(user: $user))->handle());
        foreach ([$dubai, $sharjah, $elsewhere] as $satellite) {
            $works = (new GetAccessScopeJob(user: $user, satellite: $satellite))->handle()->canWorkHere();
            expect(in_array($satellite->id, $open, true))->toBe($works, "{$who} at satellite {$satellite->id}");
        }
    }
});
