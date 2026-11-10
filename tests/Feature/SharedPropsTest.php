<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Enums\RoleEnum;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\classAdminOf;
use function Tests\Support\familyAt;
use function Tests\Support\grantRole;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\staffMember;

/*
 * The `church` prop is shared by HandleInertiaRequests for every page about a church (refactor 06: it was built by ten
 * Features and declared by ten pages): the address's satellite's church on staff and registration pages, the parent's
 * own on family pages. The `satellite` prop is the satellite the address names (Roy 2026-11-07). Fields are
 * snake_case, like every prop.
 */

it('gives a staff page the church it is about', function (): void {
    $church = church(['slug' => 'ccf', 'name' => 'CCF']);
    $dubai = satelliteOf($church, ['name' => 'CCF Dubai', 'slug' => 'dubai']);
    $admin = staffMember(RoleEnum::Admin, atSatellite($dubai));

    actingAs($admin)->get('/dubai/serve/families')->assertInertia(fn (AssertableInertia $p) => $p
        ->where('church', ['name' => 'CCF', 'slug' => 'ccf'])
        ->where('satellite', ['id' => $dubai->id, 'name' => 'CCF Dubai', 'slug' => 'dubai'])
        ->where('church_nav', ['area' => 'serve', 'can_use_admin' => true, 'can_use_full_admin' => true, 'can_manage_people' => true, 'can_manage_classes' => true, 'can_see_attendance' => true, 'can_manage_cards' => true, 'can_move_up' => true, 'can_manage_links' => true, 'can_set_password' => true]));
});

it('gives a class admin the Admin area for their class only (TODO #9)', function (): void {
    $church = church(['slug' => 'ccf']);
    $classAdmin = classAdminOf(scheduleAt(satelliteOf($church, ['slug' => 'dubai'])));

    actingAs($classAdmin)->get('/dubai/serve')->assertInertia(fn (AssertableInertia $p) => $p
        ->where('church_nav.can_use_admin', true)->where('church_nav.can_use_full_admin', false)->where('church_nav.can_manage_classes', true));
    actingAs($classAdmin)->get('/dubai')->assertRedirect('/dubai/serve');
});

it('tells the staff menu which area the page is in (Roy 2026-11-06)', function (): void {
    $church = church(['slug' => 'ccf']);
    $dubai = satelliteOf($church, ['slug' => 'dubai']);
    $admin = staffMember(RoleEnum::Admin, atSatellite($dubai));

    // People is now a satellite's Admin page like the rest (DECISIONS 2026-11-08): area `admin`, no church-wide area.
    actingAs($admin)->get('/dubai/admin/families')->assertInertia(fn (AssertableInertia $p) => $p->where('church_nav.area', 'admin'));
    actingAs($admin)->get('/dubai/admin/people')->assertInertia(fn (AssertableInertia $p) => $p->where('church_nav.area', 'admin'));
    actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dubai)))->get('/dubai/serve')
        ->assertInertia(fn (AssertableInertia $p) => $p->where('church_nav.area', 'serve')->where('church_nav.can_use_admin', false));
});

it('gives a parent\'s family page its church', function (): void {
    $church = church(['slug' => 'ccf', 'name' => 'CCF']);
    $user = User::factory()->create();
    guardianOf(familyAt(satelliteOf($church)), $user);

    // The church is never in the address (Roy 2026-11-07): a family page's church is the parent's own.
    actingAs($user)->get('/family')->assertInertia(fn (AssertableInertia $p) => $p->where('church', ['name' => 'CCF', 'slug' => 'ccf']));
});

it('gives the public thank-you page its church', function (): void {
    $dubai = satelliteOf(church(['slug' => 'ccf', 'name' => 'CCF']), ['name' => 'CCF Dubai', 'slug' => 'dubai']);

    get('/dubai/register/thanks')->assertInertia(fn (AssertableInertia $p) => $p
        ->component('Public/RegisterThanks')
        ->where('church', ['name' => 'CCF', 'slug' => 'ccf'])
        ->where('satellite', ['id' => $dubai->id, 'name' => 'CCF Dubai', 'slug' => 'dubai']));
});

it('shares no church on a page whose address names none', function (): void {
    get('/login')->assertInertia(fn (AssertableInertia $p) => $p->where('church', null)->where('satellite', null));
});

it('gives the staff menu only to a staff page — never to the next request in the same app', function (): void {
    $church = church(['slug' => 'ccf']);
    $parent = User::factory()->create();
    $dubai = satelliteOf($church, ['slug' => 'dubai']);
    guardianOf(familyAt($dubai), $parent);

    actingAs(staffMember(RoleEnum::Admin, atSatellite($dubai)))->get('/dubai/serve/families')
        ->assertInertia(fn (AssertableInertia $p) => $p->where('church_nav.can_manage_people', true));
    actingAs($parent)->get('/family')->assertInertia(fn (AssertableInertia $p) => $p->where('church_nav', null));
});

// The bar's satellite switch (spec item 13): a staff page carries the satellites its viewer may open; no other page.
it('gives a staff page the satellites its viewer may open, for the bar — and no other page', function (): void {
    $church = church(['slug' => 'ccf']);
    $dubai = satelliteOf($church, ['name' => 'CCF Dubai', 'slug' => 'dubai']);
    $sharjah = satelliteOf($church, ['name' => 'CCF Sharjah', 'slug' => 'sharjah']);
    satelliteOf($church, ['name' => 'CCF Abu Dhabi', 'slug' => 'abu-dhabi']);
    $admin = staffMember(RoleEnum::Admin, atSatellite($dubai));
    grantRole($admin, RoleEnum::Lifeshaper, atSatellite($sharjah));

    actingAs($admin)->get('/dubai/serve')->assertInertia(fn (AssertableInertia $p) => $p
        ->where('open_satellites', [['name' => 'CCF Dubai', 'slug' => 'dubai'], ['name' => 'CCF Sharjah', 'slug' => 'sharjah']]));
    get('/dubai/register')->assertInertia(fn (AssertableInertia $p) => $p->where('open_satellites', []));
});
