<?php

declare(strict_types=1);

use App\Data\Models\Church;
use App\Data\Models\Satellite;
use App\Data\Models\Schedule;
use App\Data\Models\User;
use App\Enums\RoleEnum;
use App\Enums\WeekdayEnum;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Tests\Support\atChurch;
use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\classAdminOf;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\staffMember;

/*
 * Classes (GLOSSARY "Schedule", shown as "class" — DECISIONS 2026-10-26): admins make a satellite's classes —
 * a name, an optional day and start/end time. Church admins at every satellite, satellite admins at their own.
 * AccessScope::canManageClasses/canManageClassesAt own the rule; these prove the screens ask it.
 */

/** @return array{church: Church, dxb: Satellite, house: Satellite, admin: User, onePm: Schedule} */
function classesChurch(): array
{
    $church = church(['slug' => 'ccf', 'name' => 'CCF']);
    $dxb = satelliteOf($church, ['name' => 'CCF DXB', 'slug' => 'dxb']);
    $house = satelliteOf($church, ['name' => 'House Church', 'slug' => 'house']);
    $onePm = scheduleAt($dxb, ['name' => '1PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '13:00', 'ends_at' => '15:00']);
    scheduleAt($dxb, ['name' => '7PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '19:00', 'ends_at' => '21:00']);
    scheduleAt($house, ['name' => '10AM', 'weekday' => WeekdayEnum::Sunday, 'starts_at' => '10:00', 'ends_at' => null]);
    scheduleAt($dxb, ['name' => 'Old class', 'is_active' => false]);

    return ['church' => $church, 'dxb' => $dxb, 'house' => $house, 'admin' => staffMember(RoleEnum::Admin, atChurch($church)), 'onePm' => $onePm];
}

/**
 * @param  array<mixed>  $overrides
 * @return array<mixed>
 */
function classForm(array $overrides = []): array
{
    return ['name' => 'Special class', 'weekday' => null, 'starts_at' => null, 'ends_at' => null, ...$overrides];
}

describe('the Classes page', function (): void {
    it('shows a church admin every satellite\'s active classes, in the order of the week @story#95', function (): void {
        ['admin' => $admin] = classesChurch();

        // DECISIONS 2026-11-08: the page shows only the satellite in its address — a church admin sees each at its own.
        actingAs($admin)->get('/dxb/admin/classes')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Church/Schedules/Index')
            ->has('satellites', 1)
            ->where('satellites.0.name', 'CCF DXB')
            ->where('satellites.0.schedules.0.name', '1PM')
            ->where('satellites.0.schedules.0.weekday', 'saturday')
            ->where('satellites.0.schedules.0.weekday_label', 'Saturdays')
            ->where('satellites.0.schedules.0.starts_at', '13:00')
            ->where('satellites.0.schedules.0.ends_at', '15:00')
            ->where('satellites.0.schedules.1.name', '7PM')
            ->has('satellites.0.schedules', 2)
            ->has('enums.weekday', 7));
        actingAs($admin)->get('/house/admin/classes')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->has('satellites', 1)
            ->where('satellites.0.name', 'House Church')
            ->where('satellites.0.schedules.0.ends_at', null));
    });

    it('shows a satellite admin only their own satellite\'s classes @story#95', function (): void {
        ['house' => $house] = classesChurch();

        $houseAdmin = staffMember(RoleEnum::Admin, atSatellite($house));
        actingAs($houseAdmin)->get('/house/admin/classes')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->has('satellites', 1)
            ->where('satellites.0.name', 'House Church')
            ->where('satellites.0.schedules.0.name', '10AM'));
        actingAs($houseAdmin)->get('/dxb/admin/classes')->assertNotFound();
    });

    it('puts Classes in the menu for admins only', function (): void {
        ['church' => $church, 'admin' => $admin] = classesChurch();

        actingAs($admin)->get('/dxb/serve/families')->assertInertia(fn (AssertableInertia $page) => $page->where('church_nav.can_manage_classes', true));
        actingAs(staffMember(RoleEnum::Lifeshaper, atChurch($church)))->get('/dxb/serve/families')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('church_nav.can_manage_classes', false));
    });

    it('is not there for a Lifeshaper @story#96', function (): void {
        ['church' => $church, 'dxb' => $dxb] = classesChurch();

        // DECISIONS 2026-11-08: a Lifeshaper opening an Admin page is sent to the door.
        actingAs(staffMember(RoleEnum::Lifeshaper, atChurch($church)))->get('/dxb/admin/classes')->assertRedirect('/dxb/serve/door');
        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)))->get('/dxb/admin/classes')->assertRedirect('/dxb/serve/door');
    });
});

describe('adding a class', function (): void {
    it('adds a class with no day and no times @story#97', function (): void {
        ['dxb' => $dxb, 'admin' => $admin] = classesChurch();

        actingAs($admin)->post('/dxb/admin/classes', classForm())
            ->assertRedirect()->assertSessionHas('success', 'Added "Special class" at CCF DXB.');

        $added = Schedule::query()->where('name', 'Special class')->sole();
        expect([$added->satellite_id, $added->church_id, $added->weekday, $added->startsAt(), $added->endsAt(), $added->is_active])
            ->toBe([$dxb->id, $dxb->church_id, null, null, null, true]);
    });

    it('adds a class with a day and times @story#97', function (): void {
        ['house' => $house] = classesChurch();

        actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))
            ->post('/house/admin/classes', classForm(['name' => '4PM', 'weekday' => 'saturday', 'starts_at' => '16:00', 'ends_at' => '17:30']))
            ->assertSessionHas('success');

        $added = Schedule::query()->where('name', '4PM')->sole();
        expect([$added->weekday, $added->startsAt(), $added->endsAt()])->toBe([WeekdayEnum::Saturday, '16:00', '17:30']);
    });

    it('accepts an end with no start — there is nothing for it to come after @story#97', function (): void {
        ['dxb' => $dxb, 'admin' => $admin] = classesChurch();

        actingAs($admin)->post('/dxb/admin/classes', classForm(['ends_at' => '15:00']))->assertSessionHas('success');

        expect(Schedule::query()->where('name', 'Special class')->sole()->endsAt())->toBe('15:00');
    });

    it('refuses a satellite admin another satellite @story#98', function (): void {
        ['house' => $house] = classesChurch();

        actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))->post('/dxb/admin/classes', classForm())->assertNotFound();

        expect(Schedule::query()->where('name', 'Special class')->exists())->toBeFalse();
    });

    it('adds at the satellite in the address only — a satellite named in the form, this church\'s or another\'s, is not read (DECISIONS 2026-11-08) @story#98', function (): void {
        ['dxb' => $dxb, 'house' => $house, 'admin' => $admin] = classesChurch();
        $elsewhere = satelliteOf(church());

        actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))->post('/house/admin/classes', classForm(['satellite_id' => $dxb->id, 'name' => 'At house']))->assertSessionHas('success');
        actingAs($admin)->post('/dxb/admin/classes', classForm(['satellite_id' => $elsewhere->id, 'name' => 'At dxb']))->assertSessionHas('success');

        expect(Schedule::query()->where('name', 'At house')->sole()->satellite_id)->toBe($house->id)
            ->and(Schedule::query()->where('name', 'At dxb')->sole()->satellite_id)->toBe($dxb->id)
            ->and(Schedule::query()->where('satellite_id', $elsewhere->id)->exists())->toBeFalse();
    });

    it('asks for a name, times as HH:MM, and an end after the start @story#98', function (array $form, string $field, string $message): void {
        ['dxb' => $dxb, 'admin' => $admin] = classesChurch();

        actingAs($admin)->post('/dxb/admin/classes', classForm($form))
            ->assertSessionHasErrors([$field => $message]);

        expect(Schedule::query()->count())->toBe(4);
    })->with([
        'no name' => [['name' => ''], 'name', 'Please type a name for the class.'],
        'a start that is not a time' => [['starts_at' => '1pm'], 'starts_at', 'Please type the time as HH:MM, e.g. 13:00.'],
        'an end that is not a time' => [['ends_at' => '25:00'], 'ends_at', 'Please type the time as HH:MM, e.g. 13:00.'],
        'an end before the start' => [['starts_at' => '15:00', 'ends_at' => '13:00'], 'ends_at', 'The class must end after it starts.'],
        'an end at the start' => [['starts_at' => '13:00', 'ends_at' => '13:00'], 'ends_at', 'The class must end after it starts.'],
        'a day that is not a day' => [['weekday' => 'someday'], 'weekday', 'Please choose a day, or no set day.'],
    ]);

    it('refuses a name another class at the satellite already has, but not a retired one\'s @story#98', function (): void {
        ['dxb' => $dxb, 'admin' => $admin] = classesChurch();

        actingAs($admin)->post('/dxb/admin/classes', classForm(['name' => '1PM']))
            ->assertSessionHasErrors(['name' => 'CCF DXB already has a class called "1PM".']);
        actingAs($admin)->post('/dxb/admin/classes', classForm(['name' => 'Old class']))
            ->assertSessionHas('success');

        expect(Schedule::query()->where('name', '1PM')->count())->toBe(1);
    });
});

describe('editing a class', function (): void {
    it('changes a class\'s name, day and times @story#99', function (): void {
        ['admin' => $admin, 'onePm' => $onePm] = classesChurch();

        actingAs($admin)->put("/dxb/admin/classes/{$onePm->id}", classForm(['name' => '1PM', 'weekday' => 'saturday', 'starts_at' => '13:30', 'ends_at' => '15:00']))
            ->assertRedirect()->assertSessionHas('success', 'Saved "1PM".');

        expect([$onePm->fresh()?->startsAt(), $onePm->fresh()?->endsAt()])->toBe(['13:30', '15:00']);
    });

    it('refuses another satellite\'s admin, a Lifeshaper, a retired class and a taken name @story#100', function (): void {
        ['church' => $church, 'dxb' => $dxb, 'house' => $house, 'admin' => $admin, 'onePm' => $onePm] = classesChurch();
        $retired = Schedule::query()->where('name', 'Old class')->sole();

        actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))->put("/house/admin/classes/{$onePm->id}", classForm(['name' => 'Taken']))->assertNotFound();
        actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))->put("/dxb/admin/classes/{$onePm->id}", classForm(['name' => 'Taken']))->assertNotFound();
        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)))->put("/dxb/admin/classes/{$onePm->id}", classForm(['name' => 'Taken']))->assertNotFound();
        actingAs($admin)->put("/dxb/admin/classes/{$retired->id}", classForm(['name' => 'Back']))->assertNotFound();
        actingAs($admin)->put("/dxb/admin/classes/{$onePm->id}", classForm(['name' => '7PM']))
            ->assertSessionHasErrors(['name' => 'CCF DXB already has a class called "7PM".']);
        actingAs($admin)->put("/dxb/admin/classes/{$onePm->id}", classForm(['name' => '1PM', 'starts_at' => '15:00', 'ends_at' => '14:00']))
            ->assertSessionHasErrors(['ends_at' => 'The class must end after it starts.']);

        expect([$onePm->fresh()?->name, $onePm->fresh()?->startsAt()])->toBe(['1PM', '13:00'])
            ->and($church->id)->toBe($onePm->church_id);
    });
});

describe('retiring a class', function (): void {
    it('retires a class: it leaves the page and its name is free again @story#101', function (): void {
        ['dxb' => $dxb, 'admin' => $admin, 'onePm' => $onePm] = classesChurch();

        actingAs(staffMember(RoleEnum::Admin, atSatellite($dxb)))->put("/dxb/admin/classes/{$onePm->id}/retire")
            ->assertRedirect()->assertSessionHas('success', 'Retired "1PM". It no longer shows at the door.');

        expect($onePm->fresh()?->is_active)->toBeFalse();
        actingAs($admin)->get('/dxb/admin/classes')->assertInertia(fn (AssertableInertia $page) => $page
            ->has('satellites.0.schedules', 1)
            ->where('satellites.0.schedules.0.name', '7PM'));
    });

    it('refuses another satellite\'s admin, a Lifeshaper and another church\'s admin @story#102', function (): void {
        ['house' => $house, 'onePm' => $onePm] = classesChurch();
        $otherChurch = church(['slug' => 'other']);

        $otherSatellite = satelliteOf($otherChurch, ['slug' => 'other']);

        actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))->put("/house/admin/classes/{$onePm->id}/retire")->assertNotFound();
        actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))->put("/dxb/admin/classes/{$onePm->id}/retire")->assertNotFound();
        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($house)))->put("/house/admin/classes/{$onePm->id}/retire")->assertNotFound();
        actingAs(staffMember(RoleEnum::Admin, atChurch($otherChurch)))->put("/{$otherSatellite->slug}/admin/classes/{$onePm->id}/retire")->assertNotFound();

        expect($onePm->fresh()?->is_active)->toBeTrue();
    });
});

/*
 * A class admin (TODO #9, plan §3.2 "Class admin (S)"): the Admin Role at one class — they see and change only that
 * class; they never add one.
 */
describe('a class admin', function (): void {
    it('sees only their own class, may edit and retire it, and is not offered "Add a class" @story#182', function (): void {
        ['church' => $church, 'dxb' => $dxb, 'onePm' => $onePm] = classesChurch();
        $classAdmin = classAdminOf($onePm);

        actingAs($classAdmin)->get('/dxb/admin/classes')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
            ->has('satellites', 1)
            ->where('satellites.0.name', 'CCF DXB')
            ->where('satellites.0.can_add', false)
            ->has('satellites.0.schedules', 1)
            ->where('satellites.0.schedules.0.name', '1PM'));
        actingAs($classAdmin)->put("/dxb/admin/classes/{$onePm->id}", ['name' => '1:30PM', 'weekday' => 'saturday', 'starts_at' => '13:30', 'ends_at' => '15:00'])
            ->assertSessionHas('success');
        actingAs($classAdmin)->put("/dxb/admin/classes/{$onePm->id}/retire")->assertSessionHas('success');

        expect([$onePm->fresh()?->name, $onePm->fresh()?->is_active])->toBe(['1:30PM', false]);
    });

    it('cannot change another class, add one, or open any other Admin page @story#183', function (): void {
        ['church' => $church, 'dxb' => $dxb, 'onePm' => $onePm] = classesChurch();
        $sevenPm = Schedule::query()->where('name', '7PM')->sole();
        $classAdmin = classAdminOf($onePm);

        actingAs($classAdmin)->put("/dxb/admin/classes/{$sevenPm->id}", ['name' => 'Nope', 'weekday' => null, 'starts_at' => null, 'ends_at' => null])->assertNotFound();
        actingAs($classAdmin)->put("/dxb/admin/classes/{$sevenPm->id}/retire")->assertNotFound();
        actingAs($classAdmin)->post('/dxb/admin/classes', ['name' => 'Nope', 'weekday' => null, 'starts_at' => null, 'ends_at' => null])->assertNotFound();
        // DECISIONS 2026-11-08: a class admin opening a full-Admin page is sent to the door.
        foreach (['/dxb/admin/families', '/dxb/admin/children', '/dxb/admin/card-requests', '/dxb/admin/links', '/dxb/admin/people'] as $page) {
            actingAs($classAdmin)->get($page)->assertRedirect('/dxb/serve/door');
        }

        expect([$sevenPm->fresh()?->name, Schedule::query()->where('name', 'Nope')->exists()])->toBe(['7PM', false]);
    });
});
