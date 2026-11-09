<?php

declare(strict_types=1);

use App\Data\Models\Satellite;
use App\Data\Models\Squad;
use App\Data\Models\User;
use App\Enums\RoleEnum;
use App\Foundation\Support\SatelliteCode;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;
use function Tests\Support\staffMember;

/*
 * The platform admin's area (spec item 12, `/platform`): the church's satellites, all its people and their Roles, and
 * its settings. Only the platform admin — set at the terminal — opens it; for anyone else it is an address nobody has.
 */

function platformAdmin(): User
{
    return User::factory()->create(['name' => 'Pat Platform', 'is_platform_admin' => true]);
}

it('opens only for the platform admin: an admin, a Lifeshaper or a parent finds nothing there @story#190 @story#191', function (): void {
    $church = church(['name' => 'CCF']);
    $dubai = satelliteOf($church, ['name' => 'CCF Dubai', 'slug' => 'dubai', 'code' => 'k7m2q9xa']);
    $parent = User::factory()->create();
    guardianOf(familyAt($dubai), $parent);

    get('/platform')->assertRedirect('/login');
    foreach ([staffMember(RoleEnum::Admin, atSatellite($dubai)), staffMember(RoleEnum::Lifeshaper, atSatellite($dubai)), $parent] as $notPlatform) {
        actingAs($notPlatform)->get('/platform')->assertNotFound();
    }

    actingAs(platformAdmin())->get('/platform')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
        ->component('Platform/Satellites')
        ->where('church.name', 'CCF')
        ->where('satellites', [['id' => $dubai->id, 'name' => 'CCF Dubai', 'slug' => 'dubai', 'code' => 'k7m2q9xa', 'admins' => 1]]));
});

describe('adding a satellite', function (): void {
    it('adds one with its address, a QR code that never changes, and its Squads @story#192', function (): void {
        $church = church();
        satelliteOf($church, ['slug' => 'dubai']);

        actingAs(platformAdmin())->post('/platform/satellites', ['name' => 'CCF Al Ain', 'slug' => 'al-ain'])
            ->assertRedirect('/platform')->assertSessionHas('success', 'Added CCF Al Ain at /al-ain. Give it its first admin on People.');

        $alAin = Satellite::query()->where('slug', 'al-ain')->sole();
        expect([$alAin->name, $alAin->church_id])->toBe(['CCF Al Ain', $church->id])
            ->and(SatelliteCode::looksLikeOne($alAin->code))->toBeTrue()
            ->and(Squad::query()->where('satellite_id', $alAin->id)->count())->toBe(7);
        get('/al-ain/register')->assertOk();
    });

    it('refuses an address that is taken, one the app uses, one shaped like a QR code, or not plain @story#193', function (string $slug): void {
        satelliteOf(church(), ['slug' => 'dubai']);

        actingAs(platformAdmin())->post('/platform/satellites', ['name' => 'CCF X', 'slug' => $slug])
            ->assertSessionHasErrors('slug');
        expect(Satellite::query()->count())->toBe(1);
    })->with(['taken' => 'dubai', 'an app page' => 'login', 'the picker' => 'pick', 'the platform' => 'platform', 'a dash at the end' => 'al-ain-', 'two dashes' => 'al--ain', 'code-shaped' => 'k7m2q9xa', 'not plain' => 'Al Ain', 'empty' => '']);

    it('is not for anyone but the platform admin @story#191', function (): void {
        $dubai = satelliteOf(church(), ['slug' => 'dubai']);

        actingAs(staffMember(RoleEnum::Admin, atSatellite($dubai)))->post('/platform/satellites', ['name' => 'CCF X', 'slug' => 'x'])->assertNotFound();
        expect(Satellite::query()->count())->toBe(1);
    });
});

describe('renaming a satellite, or changing its address', function (): void {
    it('renames it and moves its address; its printed QR code still finds it @story#194', function (): void {
        $dubai = satelliteOf(church(), ['name' => 'CCF Dxb', 'slug' => 'dubai', 'code' => 'k7m2q9xa']);

        actingAs(platformAdmin())->put('/platform/satellites/dubai', ['name' => 'CCF Dubai', 'slug' => 'dubai-north'])
            ->assertRedirect('/platform')->assertSessionHas('success', 'CCF Dubai is at /dubai-north.');

        expect([$dubai->fresh()?->name, $dubai->fresh()?->slug, $dubai->fresh()?->code])->toBe(['CCF Dubai', 'dubai-north', 'k7m2q9xa']);
        get('/k7m2q9xa/register')->assertRedirect('/dubai-north/register');
        get('/dubai/register')->assertNotFound();
    });

    it('keeps its own address when only the name changes, and refuses another\'s @story#195', function (): void {
        $church = church();
        satelliteOf($church, ['slug' => 'dubai']);
        satelliteOf($church, ['slug' => 'sharjah']);

        actingAs(platformAdmin())->put('/platform/satellites/dubai', ['name' => 'CCF Dubai', 'slug' => 'dubai'])->assertSessionHasNoErrors();
        actingAs(platformAdmin())->put('/platform/satellites/dubai', ['name' => 'CCF Dubai', 'slug' => 'sharjah'])->assertSessionHasErrors('slug');
        actingAs(platformAdmin())->put('/platform/satellites/dubai', ['name' => 'CCF Dubai', 'slug' => 'k7m2q9xa'])->assertSessionHasErrors('slug');
    });
});

it('tells every page whether its viewer is the platform admin — the bar\'s shortcut to /platform @story#190', function (): void {
    $dubai = satelliteOf(church(), ['slug' => 'dubai']);
    $admin = staffMember(RoleEnum::Admin, atSatellite($dubai));

    actingAs(platformAdmin())->get('/dubai/admin/families')->assertInertia(fn (AssertableInertia $p) => $p->where('auth.is_platform_admin', true));
    actingAs($admin)->get('/dubai/admin/families')->assertInertia(fn (AssertableInertia $p) => $p->where('auth.is_platform_admin', false));
});
