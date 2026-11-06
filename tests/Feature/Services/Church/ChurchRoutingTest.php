<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Enums\RoleEnum;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Tests\Support\atChurch;
use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\staffMember;

/*
 * The church's pages live at the top of the URL (/ccf/nxtgen/serve/door), so the church slug is a wildcard
 * that could swallow every fixed path. These pin the order in bootstrap/providers.php.
 */

it('keeps the fixed top-level pages reachable', function (string $path, int $status): void {
    get($path)->assertStatus($status);
})->with([
    'login' => ['/login', 200],
    'health' => ['/up', 200],
    'home needs a login' => ['/home', 302],
]);

it('serves the staff pages by area — church Admin, NxtGen Admin and NxtGen Serve (Roy 2026-11-06)', function (): void {
    $church = church(['slug' => 'ccf']);

    get('/ccf/nxtgen/serve/families')->assertRedirect('/login');
    get('/ccf/families')->assertNotFound();

    actingAs(User::factory()->create());
    // Someone with no role in this church sees nothing there at all (RequireAssignment answers 404).
    get('/ccf/nxtgen/serve/families/new')->assertNotFound();
    expect([
        route('admin.people.index', ['church' => $church->slug], false),
        route('nxtgen.admin.families.index', ['church' => $church->slug], false),
        route('nxtgen.serve.families.register', ['church' => $church->slug], false),
    ])->toBe(['/ccf/admin/people', '/ccf/nxtgen/admin/families', '/ccf/nxtgen/serve/families/new']);
});

it('opens the church home on an admin\'s Admin area and everyone else\'s Serve area @story#42', function (): void {
    $church = church(['slug' => 'ccf']);

    actingAs(staffMember(RoleEnum::Admin, atChurch($church)))->get('/ccf')->assertRedirect('/ccf/nxtgen/admin/families');
    actingAs(staffMember(RoleEnum::Admin, atSatellite(satelliteOf($church))))->get('/ccf')->assertRedirect('/ccf/nxtgen/admin/families');
    actingAs(staffMember(RoleEnum::Lifeshaper, atChurch($church)))->get('/ccf')->assertRedirect('/ccf/nxtgen/serve/door');
});

it('keeps the Admin areas to admins: a Lifeshaper gets 404 there, and Serve opens for them', function (): void {
    $church = church(['slug' => 'ccf']);
    $lifeshaper = staffMember(RoleEnum::Lifeshaper, atChurch($church));

    actingAs($lifeshaper)->get('/ccf/nxtgen/admin/families')->assertNotFound();
    actingAs($lifeshaper)->get('/ccf/admin/people')->assertNotFound();
    actingAs($lifeshaper)->get('/ccf/nxtgen/serve/families')->assertOk();
});
