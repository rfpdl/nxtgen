<?php

declare(strict_types=1);

use App\Data\Models\Church;
use App\Data\Models\Satellite;
use App\Data\Models\Schedule;
use App\Data\Models\User;
use App\Enums\RoleEnum;
use App\Enums\WeekdayEnum;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\travelTo;
use function Tests\Support\atSatellite;
use function Tests\Support\church;
use function Tests\Support\classAdminOf;
use function Tests\Support\grantRole;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\staffMember;

/*
 * Classes (GLOSSARY "Schedule", shown as "class" — DECISIONS 2026-10-26): admins make a satellite's classes —
 * a name, weekly on a day or one-time on a date (spec item 14), and a start and an end time. A satellite's admins, at that satellite (DECISIONS 2026-11-08: no
 * church-wide Role — `admin` here holds the Admin Role at both satellites).
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
    scheduleAt($house, ['name' => '10AM', 'weekday' => WeekdayEnum::Sunday, 'starts_at' => '10:00', 'ends_at' => '11:30']);
    scheduleAt($dxb, ['name' => 'Old class', 'is_active' => false]);
    scheduleAt($dxb, ['name' => 'Christmas party', 'weekday' => null, 'date' => '2026-12-24', 'starts_at' => '18:00', 'ends_at' => '20:00']);
    scheduleAt($dxb, ['name' => 'Halloween party', 'weekday' => null, 'date' => '2026-10-31', 'starts_at' => '16:00', 'ends_at' => '18:00']);

    $admin = staffMember(RoleEnum::Admin, atSatellite($dxb));
    grantRole($admin, RoleEnum::Admin, atSatellite($house));

    return ['church' => $church, 'dxb' => $dxb, 'house' => $house, 'admin' => $admin, 'onePm' => $onePm];
}

/**
 * @param  array<mixed>  $overrides
 * @return array<mixed>
 */
function classForm(array $overrides = []): array
{
    return ['name' => 'Special class', 'one_time' => false, 'weekday' => 'saturday', 'date' => null, 'starts_at' => '16:00', 'ends_at' => '17:30', ...$overrides];
}

describe('the Classes page', function (): void {
    beforeEach(fn () => travelTo(CarbonImmutable::parse('2026-11-10 12:00', 'Asia/Dubai')));

    it('shows an admin of both satellites each satellite\'s active classes, in the order of the week @story#95', function (): void {
        ['admin' => $admin] = classesChurch();

        // DECISIONS 2026-11-08: the page shows only the satellite in its address — an admin of both sees each at its own.
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
            ->where('satellites.0.schedules.2.name', 'Christmas party')
            ->where('satellites.0.schedules.2.weekday', null)
            ->where('satellites.0.schedules.2.date', '2026-12-24')
            ->where('satellites.0.schedules.2.date_label', 'Thu 24 Dec 2026')
            ->has('satellites.0.schedules', 3)
            ->has('enums.weekday', 7));
        actingAs($admin)->get('/house/admin/classes')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->has('satellites', 1)
            ->where('satellites.0.name', 'House Church')
            ->where('satellites.0.schedules.0.ends_at', '11:30'));
    });

    it('puts a one-time class whose date has gone on a "Past" list, newest first (Roy 2026-11-10) @story#95', function (): void {
        ['dxb' => $dxb, 'admin' => $admin] = classesChurch();
        scheduleAt($dxb, ['name' => 'True Life Day 1', 'weekday' => null, 'date' => '2026-11-09']);

        actingAs($admin)->get('/dxb/admin/classes')->assertInertia(fn (AssertableInertia $page) => $page
            ->has('satellites.0.schedules', 3)
            ->has('satellites.0.past', 2)
            ->where('satellites.0.past.0.name', 'True Life Day 1')
            ->where('satellites.0.past.1.name', 'Halloween party'));
        travelTo(CarbonImmutable::parse('2026-12-25 00:30', 'Asia/Dubai'));
        actingAs($admin)->get('/dxb/admin/classes')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('satellites.0.past.0.name', 'Christmas party'));
    });

    it('puts a one-time class added for a date already gone straight on "Past" @story#97', function (): void {
        ['admin' => $admin] = classesChurch();

        actingAs($admin)->post('/dxb/admin/classes', classForm(['name' => 'Missed', 'one_time' => true, 'date' => '2026-11-01']))->assertSessionHas('success');

        actingAs($admin)->get('/dxb/admin/classes')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('satellites.0.past.0.name', 'Missed'));
    });

    it('shows a class admin their own one-time class on "Past" once its date has gone (TODO #9) @story#182', function (): void {
        ['dxb' => $dxb] = classesChurch();
        $party = Schedule::query()->where('name', 'Halloween party')->sole();

        actingAs(classAdminOf($party))->get('/dxb/admin/classes')->assertInertia(fn (AssertableInertia $page) => $page
            ->has('satellites.0.schedules', 0)
            ->has('satellites.0.past', 1)
            ->where('satellites.0.past.0.name', 'Halloween party'));
    });

    it('sends a stranger to sign in', function (): void {
        classesChurch();

        get('/dxb/admin/classes')->assertRedirect('/login');
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
        ['dxb' => $dxb, 'admin' => $admin] = classesChurch();

        actingAs($admin)->get('/dxb/serve/families')->assertInertia(fn (AssertableInertia $page) => $page->where('church_nav.can_manage_classes', true));
        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)))->get('/dxb/serve/families')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('church_nav.can_manage_classes', false));
    });

    it('is not there for a Lifeshaper @story#96', function (): void {
        ['dxb' => $dxb, 'house' => $house] = classesChurch();

        // DECISIONS 2026-11-08: a Lifeshaper opening an Admin page is sent to the door — one at both satellites too.
        $atBoth = staffMember(RoleEnum::Lifeshaper, atSatellite($dxb));
        grantRole($atBoth, RoleEnum::Lifeshaper, atSatellite($house));
        actingAs($atBoth)->get('/dxb/admin/classes')->assertRedirect('/dxb/serve');
        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)))->get('/dxb/admin/classes')->assertRedirect('/dxb/serve');
    });
});

describe('adding a class', function (): void {
    it('adds a one-time class on its date — a day sent with it is not kept @story#97', function (): void {
        ['dxb' => $dxb, 'admin' => $admin] = classesChurch();

        actingAs($admin)->post('/dxb/admin/classes', classForm(['name' => 'True Life Day 1', 'one_time' => true, 'date' => '2026-12-28', 'starts_at' => '09:00', 'ends_at' => '12:00']))
            ->assertRedirect()->assertSessionHas('success', 'Added "True Life Day 1" at CCF DXB.');

        $added = Schedule::query()->where('name', 'True Life Day 1')->sole();
        expect([$added->satellite_id, $added->church_id, $added->weekday, $added->date?->toDateString(), $added->startsAt(), $added->endsAt(), $added->is_active])
            ->toBe([$dxb->id, $dxb->church_id, null, '2026-12-28', '09:00', '12:00', true]);
    });

    it('adds a class with a day and times @story#97', function (): void {
        ['house' => $house] = classesChurch();

        actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))
            ->post('/house/admin/classes', classForm(['name' => '4PM', 'weekday' => 'saturday', 'starts_at' => '16:00', 'ends_at' => '17:30']))
            ->assertSessionHas('success');

        $added = Schedule::query()->where('name', '4PM')->sole();
        expect([$added->weekday, $added->date, $added->startsAt(), $added->endsAt()])->toBe([WeekdayEnum::Saturday, null, '16:00', '17:30']);
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

    it('asks for a name, the day or the date, both times as HH:MM, and an end after the start @story#98', function (array $form, string $field, string $message): void {
        ['dxb' => $dxb, 'admin' => $admin] = classesChurch();

        actingAs($admin)->post('/dxb/admin/classes', classForm($form))
            ->assertSessionHasErrors([$field => $message]);

        expect(Schedule::query()->count())->toBe(6);
    })->with([
        'no name' => [['name' => ''], 'name', 'Please type a name for the class.'],
        'weekly with no day' => [['weekday' => null], 'weekday', 'Please choose the day it meets.'],
        'one-time with no date' => [['one_time' => true, 'weekday' => null], 'date', 'Please choose the date.'],
        'one-time with a date that is not one' => [['one_time' => true, 'date' => '24/12/2026'], 'date', 'Please choose the date.'],
        'weekly with a leftover date that is not one' => [['weekday' => null, 'date' => 'junk'], 'weekday', 'Please choose the day it meets.'],
        'no start' => [['starts_at' => null], 'starts_at', 'Please type when it starts, e.g. 13:00.'],
        'no end' => [['ends_at' => null], 'ends_at', 'Please type when it ends, e.g. 15:00.'],
        'a start that is not a time' => [['starts_at' => '1pm'], 'starts_at', 'Please type the time as HH:MM, e.g. 13:00.'],
        'an end that is not a time' => [['ends_at' => '25:00'], 'ends_at', 'Please type the time as HH:MM, e.g. 13:00.'],
        'an end before the start' => [['starts_at' => '15:00', 'ends_at' => '13:00'], 'ends_at', 'The class must end after it starts.'],
        'an end at the start' => [['starts_at' => '13:00', 'ends_at' => '13:00'], 'ends_at', 'The class must end after it starts.'],
        'a day that is not a day' => [['weekday' => 'someday'], 'weekday', 'Please choose the day it meets.'],
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

    it('reads only what one_time says — a leftover date that is not one never blocks a weekly save @story#99', function (): void {
        ['admin' => $admin, 'onePm' => $onePm] = classesChurch();

        actingAs($admin)->put("/dxb/admin/classes/{$onePm->id}", classForm(['name' => '1PM', 'date' => 'junk']))->assertSessionHas('success');

        expect([$onePm->fresh()?->weekday, $onePm->fresh()?->date])->toBe([WeekdayEnum::Saturday, null]);
    });

    it('changes no one-time class whose date has gone — retiring it is all that is left (Codex) @story#100', function (): void {
        ['admin' => $admin] = classesChurch();
        travelTo(CarbonImmutable::parse('2026-11-10 12:00', 'Asia/Dubai'));
        $halloween = Schedule::query()->where('name', 'Halloween party')->sole();

        actingAs($admin)->put("/dxb/admin/classes/{$halloween->id}", classForm(['name' => 'Renamed', 'one_time' => true, 'date' => '2026-12-31']))->assertNotFound();
        actingAs($admin)->put("/dxb/admin/classes/{$halloween->id}/retire")->assertSessionHas('success');

        expect($halloween->fresh()?->name)->toBe('Halloween party');
    });

    it('turns a weekly class into a one-time one and back @story#99', function (): void {
        ['admin' => $admin, 'onePm' => $onePm] = classesChurch();

        actingAs($admin)->put("/dxb/admin/classes/{$onePm->id}", classForm(['name' => '1PM', 'one_time' => true, 'date' => '2026-12-26']))->assertSessionHas('success');
        expect([$onePm->fresh()?->weekday, $onePm->fresh()?->date?->toDateString()])->toBe([null, '2026-12-26']);

        actingAs($admin)->put("/dxb/admin/classes/{$onePm->id}", classForm(['name' => '1PM', 'date' => '2026-12-26']))->assertSessionHas('success');
        expect([$onePm->fresh()?->weekday, $onePm->fresh()?->date])->toBe([WeekdayEnum::Saturday, null]);
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
            ->has('satellites.0.schedules', 2)
            ->where('satellites.0.schedules.0.name', '7PM'));
    });

    it('refuses another satellite\'s admin, a Lifeshaper and another church\'s admin @story#102', function (): void {
        ['house' => $house, 'onePm' => $onePm] = classesChurch();
        $otherChurch = church(['slug' => 'other']);

        $otherSatellite = satelliteOf($otherChurch, ['slug' => 'other']);

        actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))->put("/house/admin/classes/{$onePm->id}/retire")->assertNotFound();
        actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))->put("/dxb/admin/classes/{$onePm->id}/retire")->assertNotFound();
        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($house)))->put("/house/admin/classes/{$onePm->id}/retire")->assertNotFound();
        actingAs(staffMember(RoleEnum::Admin, atSatellite($otherSatellite)))->put("/{$otherSatellite->slug}/admin/classes/{$onePm->id}/retire")->assertNotFound();

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
        actingAs($classAdmin)->put("/dxb/admin/classes/{$onePm->id}", classForm(['name' => '1:30PM', 'starts_at' => '13:30', 'ends_at' => '15:00']))
            ->assertSessionHas('success');
        actingAs($classAdmin)->put("/dxb/admin/classes/{$onePm->id}/retire")->assertSessionHas('success');

        expect([$onePm->fresh()?->name, $onePm->fresh()?->is_active])->toBe(['1:30PM', false]);
    });

    it('cannot change another class, add one, or open any other Admin page @story#183', function (): void {
        ['church' => $church, 'dxb' => $dxb, 'onePm' => $onePm] = classesChurch();
        $sevenPm = Schedule::query()->where('name', '7PM')->sole();
        $classAdmin = classAdminOf($onePm);

        actingAs($classAdmin)->put("/dxb/admin/classes/{$sevenPm->id}", classForm(['name' => 'Nope']))->assertNotFound();
        actingAs($classAdmin)->put("/dxb/admin/classes/{$sevenPm->id}/retire")->assertNotFound();
        actingAs($classAdmin)->post('/dxb/admin/classes', classForm(['name' => 'Nope']))->assertNotFound();
        // DECISIONS 2026-11-08: a class admin opening a full-Admin page is sent to the door.
        foreach (['/dxb/admin/families', '/dxb/admin/children', '/dxb/admin/card-requests', '/dxb/admin/links', '/dxb/admin/people'] as $page) {
            actingAs($classAdmin)->get($page)->assertRedirect('/dxb/serve');
        }

        expect([$sevenPm->fresh()?->name, Schedule::query()->where('name', 'Nope')->exists()])->toBe(['7PM', false]);
    });
});
