<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Enums\RoleEnum;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Tests\Support\atChurch;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;
use function Tests\Support\staffMember;

/*
 * The `church` prop is shared by HandleInertiaRequests for every page whose address names a church (refactor 06:
 * it was built by ten Features and declared by ten pages). Its fields are snake_case, like every prop.
 */

it('gives a staff page the church it is about', function (): void {
    $admin = staffMember(RoleEnum::Admin, atChurch(church(['slug' => 'ccf', 'name' => 'CCF'])));

    actingAs($admin)->get('/ccf/families')->assertInertia(fn (AssertableInertia $p) => $p
        ->where('church', ['name' => 'CCF', 'slug' => 'ccf'])
        ->where('churchNav.can_manage_people', true)
        ->where('churchNav.can_review_duplicates', true));
});

it('gives a parent\'s family page its church', function (): void {
    $church = church(['slug' => 'ccf', 'name' => 'CCF']);
    $user = User::factory()->create();
    guardianOf(familyAt(satelliteOf($church)), $user);

    actingAs($user)->get('/ccf/family')->assertInertia(fn (AssertableInertia $p) => $p->where('church', ['name' => 'CCF', 'slug' => 'ccf']));
});

it('gives the public thank-you page its church', function (): void {
    church(['slug' => 'ccf', 'name' => 'CCF']);

    get('/ccf/register/thanks')->assertInertia(fn (AssertableInertia $p) => $p
        ->component('Public/RegisterThanks')
        ->where('church', ['name' => 'CCF', 'slug' => 'ccf']));
});

it('shares no church on a page whose address names none', function (): void {
    get('/login')->assertInertia(fn (AssertableInertia $p) => $p->where('church', null));
});

it('gives the staff menu only to a staff page — never to the next request in the same app', function (): void {
    $church = church(['slug' => 'ccf']);
    $parent = User::factory()->create();
    guardianOf(familyAt(satelliteOf($church)), $parent);

    actingAs(staffMember(RoleEnum::Admin, atChurch($church)))->get('/ccf/families')
        ->assertInertia(fn (AssertableInertia $p) => $p->where('churchNav.can_manage_people', true));
    actingAs($parent)->get('/ccf/family')->assertInertia(fn (AssertableInertia $p) => $p->where('churchNav', null));
});
