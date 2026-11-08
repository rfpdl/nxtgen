<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Enums\RoleEnum;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\grantRole;
use function Tests\Support\satelliteOf;
use function Tests\Support\staffMember;

/*
 * A satellite's staff pages live at the top of the URL (/dubai/serve/door — the church is never in it, Roy
 * 2026-11-07), so the satellite slug is a wildcard that could swallow every fixed path. These pin the order in
 * bootstrap/providers.php.
 */

it('keeps the fixed top-level pages reachable', function (string $path, int $status): void {
    get($path)->assertStatus($status);
})->with([
    'login' => ['/login', 200],
    'health' => ['/up', 200],
    'home needs a login' => ['/home', 302],
]);

it('serves the staff pages by area — a satellite\'s Admin and Serve (Roy 2026-11-06, 2026-11-07)', function (): void {
    $dubai = satelliteOf(church(['slug' => 'ccf']), ['slug' => 'dubai']);

    get('/dubai/serve/families')->assertRedirect('/login');
    get('/dubai/families')->assertNotFound();

    actingAs(User::factory()->create());
    // Someone with no role at this satellite sees nothing there at all (RequireAssignment answers 404).
    get('/dubai/serve/families/new')->assertNotFound();
    // People is a satellite's Admin page like the rest now (DECISIONS 2026-11-08): no church-wide area.
    expect([
        route('admin.people.index', $dubai, false),
        route('admin.families.index', $dubai, false),
        route('serve.families.register', $dubai, false),
    ])->toBe(['/dubai/admin/people', '/dubai/admin/families', '/dubai/serve/families/new']);
});

it('opens the church home on an admin\'s Admin area and everyone else\'s Serve area @story#42', function (): void {
    $church = church(['slug' => 'ccf']);
    $dubai = satelliteOf($church, ['slug' => 'dubai']);
    $sharjah = satelliteOf($church, ['slug' => 'sharjah']);
    // An admin at both satellites (no church-wide Role — spec item 9).
    $homeAndAway = staffMember(RoleEnum::Admin, atSatellite($dubai));
    grantRole($homeAndAway, RoleEnum::Admin, atSatellite($sharjah));

    // The satellite's home (/{satellite}, was the church's /ccf — Roy 2026-11-07).
    actingAs($homeAndAway)->get('/dubai')->assertRedirect('/dubai/admin/families');
    actingAs($homeAndAway)->get('/sharjah')->assertRedirect('/sharjah/admin/families');
    actingAs(staffMember(RoleEnum::Admin, atSatellite($dubai)))->get('/dubai')->assertRedirect('/dubai/admin/families');
    actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dubai)))->get('/dubai')->assertRedirect('/dubai/serve/door');
    // Access is asked at the satellite in the address only (DECISIONS 2026-11-08): Dubai's admin has no Role at Sharjah.
    actingAs(staffMember(RoleEnum::Admin, atSatellite($dubai)))->get('/sharjah')->assertNotFound();
});

it('keeps the Admin area to admins: a Lifeshaper opening it is sent to Serve, and acting there is a 404', function (): void {
    $church = church(['slug' => 'ccf']);
    $dubai = satelliteOf($church, ['slug' => 'dubai']);
    $lifeshaper = staffMember(RoleEnum::Lifeshaper, atSatellite($dubai));

    // DECISIONS 2026-11-08 (spec item 10): an Admin page opened by someone who only serves here goes to their door
    // (was a 404); any Admin act by them is still a 404.
    actingAs($lifeshaper)->get('/dubai/admin/families')->assertRedirect('/dubai/serve/door');
    actingAs($lifeshaper)->get('/dubai/admin/people')->assertRedirect('/dubai/serve/door');
    actingAs($lifeshaper)->post('/dubai/admin/people', [])->assertNotFound();
    actingAs($lifeshaper)->get('/dubai/serve/families')->assertOk();
});

it('gives a Lifeshaper of one satellite the other satellite\'s Admin as not found — they serve nowhere there', function (): void {
    $church = church(['slug' => 'ccf']);
    $dubai = satelliteOf($church, ['slug' => 'dubai']);
    satelliteOf($church, ['slug' => 'sharjah']);
    $lifeshaper = staffMember(RoleEnum::Lifeshaper, atSatellite($dubai));

    actingAs($lifeshaper)->get('/dubai/admin/families')->assertRedirect('/dubai/serve/door');
    actingAs($lifeshaper)->get('/sharjah/admin/families')->assertNotFound();
    actingAs($lifeshaper)->get('/sharjah/serve/door')->assertNotFound();
});
