<?php

declare(strict_types=1);

use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;
use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\helperAt;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;
use function Tests\Support\staffMember;

/*
 * The platform's People (spec item 12): everyone who serves anywhere in the church with every Role, any Role given at
 * any satellite or class — a new satellite's first admin is given here — and removed, never a satellite's last admin.
 */

it('lists everyone who serves, at every satellite, and offers every Role anywhere @story#196', function (): void {
    $church = church();
    $dubai = satelliteOf($church, ['name' => 'CCF Dubai']);
    $sharjah = satelliteOf($church, ['name' => 'CCF Sharjah']);
    scheduleAt($dubai, ['name' => '1PM']);
    staffMember(RoleEnum::Admin, atSatellite($dubai), ['name' => 'Ana Dubai']);
    staffMember(RoleEnum::Lifeshaper, atSatellite($sharjah), ['name' => 'Ben Sharjah']);
    $platform = User::factory()->create(['is_platform_admin' => true]);

    actingAs($platform)->get('/platform/people')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
        ->component('Platform/People')
        ->where('people', fn (Collection $people): bool => $people->pluck('name')->all() === ['Ana Dubai', 'Ben Sharjah'])
        ->where('grant_options', fn (Collection $options): bool => $options->pluck('label')->all() === [
            'Admin · CCF Dubai', 'Lifeshaper · CCF Dubai', 'Admin · CCF Dubai · 1PM', 'Admin · CCF Sharjah', 'Lifeshaper · CCF Sharjah',
        ]));
});

it('gives a new satellite its first admin, who is emailed a sign-in link @story#198', function (): void {
    Notification::fake();
    $church = church();
    satelliteOf($church, ['slug' => 'dubai']);
    $platform = User::factory()->create(['is_platform_admin' => true]);
    actingAs($platform)->post('/platform/satellites', ['name' => 'CCF Al Ain', 'slug' => 'al-ain']);
    $alAin = $church->satellites()->where('slug', 'al-ain')->sole();

    actingAs($platform)->post('/platform/people', [
        'name' => 'Aya Admin', 'email' => 'aya@example.com', 'role' => 'admin', 'scope_type' => 'satellite', 'scope_id' => $alAin->id,
    ])->assertSessionHas('success', 'aya@example.com is now Admin · CCF Al Ain. We emailed them a sign-in link.');

    $aya = User::query()->where('email', 'aya@example.com')->sole();
    actingAs($aya)->get('/al-ain/admin/people')->assertOk();
});

it('removes a Role anywhere, but never a satellite\'s last admin @story#197', function (): void {
    $church = church();
    $dubai = satelliteOf($church, ['name' => 'CCF Dubai']);
    $lifeshaper = staffMember(RoleEnum::Lifeshaper, atSatellite($dubai));
    $admin = staffMember(RoleEnum::Admin, atSatellite($dubai));
    $platform = User::factory()->create(['is_platform_admin' => true]);
    $role = fn (User $u): RoleAssignment => RoleAssignment::query()->where('user_id', $u->id)->sole();

    actingAs($platform)->delete('/platform/people/roles/'.$role($lifeshaper)->id)->assertSessionHas('success', 'Role removed.');
    actingAs($platform)->delete('/platform/people/roles/'.$role($admin)->id)
        ->assertSessionHas('error', 'This is the only admin of CCF Dubai. Add another admin there first.');

    expect([$role($lifeshaper)->is_active, $role($admin)->is_active])->toBe([false, true]);
});

it('is for the platform admin only @story#191', function (): void {
    $dubai = satelliteOf(church());
    $admin = staffMember(RoleEnum::Admin, atSatellite($dubai));

    actingAs($admin)->get('/platform/people')->assertNotFound();
    actingAs($admin)->post('/platform/people', [
        'name' => 'X', 'email' => 'x@example.com', 'role' => 'admin', 'scope_type' => ScopeTypeEnum::Satellite->value, 'scope_id' => $dubai->id,
    ])->assertNotFound();
});

it('gives no helper for the day — those are a satellite\'s, on its People — nor any Role at a Session (review, issue 14) @story#212', function (string $role, string $type): void {
    $dubai = satelliteOf(church());
    $session = sessionOf(scheduleAt($dubai));
    $platform = User::factory()->platformAdmin()->create();

    actingAs($platform)->post('/platform/people', ['name' => 'X', 'email' => 'x@example.com', 'role' => $role, 'scope_type' => $type, 'scope_id' => $type === 'session' ? $session->id : $dubai->id])
        ->assertNotFound();

    expect(User::query()->where('email', 'x@example.com')->exists())->toBeFalse();
})->with([
    'a helper at a satellite' => ['helper', 'satellite'],
    'a helper at a Session' => ['helper', 'session'],
    'an admin at a Session' => ['admin', 'session'],
]);

it('names a helper for the day by their Session on its day (review, issue 14) @story#196', function (): void {
    travelTo(CarbonImmutable::parse('2026-10-31 09:00:00', 'Asia/Dubai'));
    $dubai = satelliteOf(church(['timezone' => 'Asia/Dubai']), ['name' => 'CCF Dubai']);
    helperAt(sessionOf(scheduleAt($dubai, ['name' => '1PM']), '2026-10-31'));

    actingAs(User::factory()->platformAdmin()->create())->get('/platform/people')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('people.0.roles.0.label', 'Helper for the day · CCF Dubai · 1PM today'));
});
