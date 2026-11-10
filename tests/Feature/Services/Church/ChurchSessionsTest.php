<?php

declare(strict_types=1);

use App\Data\Models\CheckIn;
use App\Data\Models\Satellite;
use App\Data\Models\Schedule;
use App\Data\Models\Session;
use App\Data\Models\User;
use App\Enums\RoleEnum;
use App\Enums\WeekdayEnum;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\put;
use function Pest\Laravel\travelTo;
use function Tests\Support\atSatellite;
use function Tests\Support\checkedIn;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\classAdminOf;
use function Tests\Support\familyAt;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;
use function Tests\Support\staffMember;

/*
 * An admin opens and closes a Session (spec item 15; Roy 2026-11-10: the Session, not "check-in") — the satellite's
 * admin, or that class's: on its date only, never another day; closed, it can be opened again the same day. Opening
 * makes it; Attendance lists every opened Session, 0 children included.
 *
 * It is Saturday 31 Oct 2026, 09:00 in Dubai, throughout.
 */

beforeEach(fn () => travelTo(CarbonImmutable::parse('2026-10-31 09:00:00', 'Asia/Dubai')));

/** @return array{dxb: Satellite, house: Satellite, onePm: Schedule, sevenPm: Schedule, sunday: Schedule, admin: User} */
function sessionsChurch(): array
{
    $church = church(['slug' => 'ccf', 'timezone' => 'Asia/Dubai']);
    $dxb = Satellite::factory()->for($church)->withSquads()->create(['name' => 'CCF DXB', 'slug' => 'dxb']);
    $house = Satellite::factory()->for($church)->withSquads()->create(['name' => 'House Church', 'slug' => 'house']);

    return [
        'dxb' => $dxb,
        'house' => $house,
        'onePm' => scheduleAt($dxb, ['name' => '1PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '13:00', 'ends_at' => '15:00']),
        'sevenPm' => scheduleAt($dxb, ['name' => '7PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '19:00', 'ends_at' => '21:00']),
        'sunday' => scheduleAt($dxb, ['name' => 'Sunday school', 'weekday' => WeekdayEnum::Sunday]),
        'admin' => staffMember(RoleEnum::Admin, atSatellite($dxb)),
    ];
}

describe('opening a Session', function (): void {
    it('opens today\'s class for a satellite admin — the Session is made, open, by them @story#202', function (): void {
        ['onePm' => $onePm, 'admin' => $admin] = sessionsChurch();

        actingAs($admin)->post("/dxb/serve/classes/{$onePm->id}/session")
            ->assertRedirect()->assertSessionHas('success', '1PM is open.');

        $session = Session::query()->sole();
        expect([$session->schedule_id, $session->date->toDateString(), $session->opened_by, $session->closed_at])
            ->toBe([$onePm->id, '2026-10-31', $admin->id, null]);
    });

    it('lets the class\'s own class admin open it, not another class @story#202', function (): void {
        ['onePm' => $onePm, 'sevenPm' => $sevenPm] = sessionsChurch();
        $classAdmin = classAdminOf($onePm);

        actingAs($classAdmin)->post("/dxb/serve/classes/{$onePm->id}/session")->assertSessionHas('success');
        actingAs($classAdmin)->post("/dxb/serve/classes/{$sevenPm->id}/session")->assertNotFound();

        expect(Session::query()->pluck('schedule_id')->all())->toBe([$onePm->id]);
    });

    it('is refused to a Lifeshaper, an admin elsewhere and a stranger @story#203', function (): void {
        ['dxb' => $dxb, 'house' => $house, 'onePm' => $onePm] = sessionsChurch();

        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)))->post("/dxb/serve/classes/{$onePm->id}/session")->assertNotFound();
        actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))->post("/dxb/serve/classes/{$onePm->id}/session")->assertNotFound();
        actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))->post("/house/serve/classes/{$onePm->id}/session")->assertNotFound();
        actingAs(User::factory()->create())->post("/dxb/serve/classes/{$onePm->id}/session")->assertNotFound();   // no Role here
        auth()->logout();
        post("/dxb/serve/classes/{$onePm->id}/session")->assertRedirect('/login');

        expect(Session::query()->count())->toBe(0);
    });

    it('opens a class on its date only, never another day @story#203', function (): void {
        ['sunday' => $sunday, 'onePm' => $onePm, 'admin' => $admin] = sessionsChurch();
        $retired = scheduleAt($onePm->satellite, ['name' => 'Old', 'is_active' => false]);

        actingAs($admin)->post("/dxb/serve/classes/{$sunday->id}/session")->assertNotFound();
        actingAs($admin)->post("/dxb/serve/classes/{$retired->id}/session")->assertNotFound();

        expect(Session::query()->count())->toBe(0);
    });

    it('opens a closed Session again the same day — the same Session, its Check-ins and who opened it kept; opening an open one changes nothing @story#202', function (): void {
        ['dxb' => $dxb, 'onePm' => $onePm, 'admin' => $admin] = sessionsChurch();
        $firstAdmin = staffMember(RoleEnum::Admin, atSatellite($dxb));
        $session = sessionOf($onePm);
        $session->update(['opened_by' => $firstAdmin->id, 'opened_at' => '2026-10-31 04:00:00', 'closed_at' => now()]);
        $in = checkedIn($session, childOf(familyAt($dxb), ['level' => 2, 'gender' => 'girl']));

        actingAs($admin)->post("/dxb/serve/classes/{$onePm->id}/session")->assertSessionHas('success', '1PM is open.');
        actingAs($admin)->post("/dxb/serve/classes/{$onePm->id}/session")->assertSessionHas('success', '1PM is open.');

        $reopened = $session->fresh();
        expect([Session::query()->count(), $reopened?->closed_at, $reopened?->opened_by, $reopened?->opened_at->toDateTimeString()])
            ->toBe([1, null, $firstAdmin->id, '2026-10-31 04:00:00'])
            ->and($in->fresh()?->isIn())->toBeTrue();
    });
});

describe('closing a Session', function (): void {
    it('closes it for an admin, saying so; the class admin of that class too @story#204', function (): void {
        ['onePm' => $onePm, 'admin' => $admin] = sessionsChurch();
        $session = sessionOf($onePm);

        actingAs($admin)->put("/{$session->code}/close")
            ->assertRedirect()->assertSessionHas('success', '1PM is closed.');
        expect([$session->fresh()?->closed_by, $session->fresh()?->isOpenOn(CarbonImmutable::parse('2026-10-31'))])->toBe([$admin->id, false]);

        $session->update(['closed_at' => null]);
        actingAs(classAdminOf($onePm))->put("/{$session->code}/close")->assertSessionHas('success');
        expect($session->fresh()?->closed_at)->not->toBeNull();
    });

    it('is refused to a Lifeshaper, another class\'s class admin, an admin elsewhere, someone with no Role and a stranger @story#205', function (): void {
        ['dxb' => $dxb, 'house' => $house, 'onePm' => $onePm, 'sevenPm' => $sevenPm, 'admin' => $admin] = sessionsChurch();
        $session = sessionOf($onePm);

        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)))->put("/{$session->code}/close")->assertNotFound();
        actingAs(classAdminOf($sevenPm))->put("/{$session->code}/close")->assertNotFound();
        actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))->put("/{$session->code}/close")->assertNotFound();

        expect($session->fresh()?->closed_at)->toBeNull();

        actingAs(User::factory()->create())->put("/{$session->code}/close")->assertNotFound();   // no Role here
        auth()->logout();
        put("/{$session->code}/close")->assertRedirect('/login');
        expect($session->fresh()?->closed_at)->toBeNull();
    });

    it('says a class another admin closed meanwhile is closed — no error page @story#204', function (): void {
        ['onePm' => $onePm, 'admin' => $admin] = sessionsChurch();
        $session = sessionOf($onePm);
        $session->update(['closed_at' => '2026-10-31 05:00:00']);

        actingAs($admin)->put("/{$session->code}/close")->assertSessionHas('success', '1PM is closed.');

        expect($session->fresh()?->closed_at?->toDateTimeString())->toBe('2026-10-31 05:00:00');
    });
});

describe('midnight at the satellite (spec item 15)', function (): void {
    it('still checks children in at 23:59 in Dubai, and nothing after midnight there — though the server\'s day is still Saturday', function (): void {
        ['dxb' => $dxb, 'onePm' => $onePm, 'admin' => $admin] = sessionsChurch();
        $session = sessionOf($onePm);
        $amara = childOf(familyAt($dxb), ['nickname' => 'Amara', 'level' => 2, 'gender' => 'girl']);
        $ben = childOf(familyAt($dxb), ['nickname' => 'Ben', 'level' => 1, 'gender' => 'boy']);
        $in = checkedIn($session, $ben);
        $tick = fn (int $id): array => ['children' => [['child_id' => $id, 'level' => null, 'make_active' => false]]];

        travelTo(CarbonImmutable::parse('2026-10-31 23:59:00', 'Asia/Dubai'));
        actingAs($admin)->post("/{$session->code}/check-ins", $tick($amara->id))->assertSessionHas('success');

        travelTo(CarbonImmutable::parse('2026-10-31 20:30:00', 'UTC'));   // 00:30 on Sunday in Dubai
        actingAs($admin)->post("/{$session->code}/check-ins", $tick($ben->id))->assertSessionHas('error', '1PM is closed.');
        actingAs($admin)->put("/{$session->code}/check-ins/{$in->id}/undo")->assertSessionHas('error', '1PM is closed.');
        actingAs($admin)->get("/{$session->code}/checkin")->assertInertia(fn (AssertableInertia $page) => $page->where('session.state', 'closed'));
        actingAs($admin)->post("/dxb/serve/classes/{$onePm->id}/session")->assertNotFound();
        actingAs($admin)->get('/dxb/serve')->assertInertia(fn (AssertableInertia $page) => $page->where('today', 'Sun 1 Nov')->where('classes.0.name', 'Sunday school'));

        expect([$in->fresh()?->isIn(), $session->fresh()?->isOpenOn(CarbonImmutable::parse('2026-11-01'))])->toBe([true, false]);
    });
});

describe('Attendance', function (): void {
    it('lists an opened Session with nobody checked in, as 0 @story#202', function (): void {
        ['onePm' => $onePm, 'admin' => $admin] = sessionsChurch();
        actingAs($admin)->post("/dxb/serve/classes/{$onePm->id}/session");
        expect(Session::query()->count())->toBe(1);

        // Attendance opens on the latest Session: this one, with nobody in.
        actingAs($admin)->get('/dxb/admin/attendance')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('class_id', $onePm->id)
            ->where('date', '2026-10-31')
            ->where('total', 0));
    });

    it('keeps the children checked in once the Session closes @story#204', function (): void {
        ['dxb' => $dxb, 'onePm' => $onePm, 'admin' => $admin] = sessionsChurch();
        $session = sessionOf($onePm);
        checkedIn($session, childOf(familyAt($dxb), ['level' => 2, 'gender' => 'girl']));
        actingAs($admin)->put("/{$session->code}/close");

        actingAs($admin)->get("/dxb/admin/attendance?class={$onePm->id}&date=2026-10-31")
            ->assertInertia(fn (AssertableInertia $page) => $page->where('total', 1));
    });
});

describe('retiring a class while it is open (Astra, Batch C)', function (): void {
    it('closes its Session: its own address takes no check-in, Undo, present tap or head count after — and stays readable @story#204', function (): void {
        ['dxb' => $dxb, 'onePm' => $onePm, 'admin' => $admin] = sessionsChurch();
        $session = sessionOf($onePm);
        $in = checkedIn($session, childOf(familyAt($dxb), ['level' => 2, 'gender' => 'girl']));
        $late = childOf(familyAt($dxb), ['level' => 1, 'gender' => 'boy']);

        actingAs($admin)->put("/dxb/admin/classes/{$onePm->id}/retire")->assertSessionHas('success');
        expect($session->fresh()?->closed_at)->not->toBeNull();

        foreach ([staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)), $admin] as $who) {
            actingAs($who)->post("/{$session->code}/check-ins", ['children' => [['child_id' => $late->id, 'level' => null, 'make_active' => false]]])->assertSessionHas('error', '1PM is closed.');
            actingAs($who)->put("/{$session->code}/check-ins/{$in->id}/undo")->assertSessionHas('error', '1PM is closed.');
            actingAs($who)->put("/{$session->code}/check-ins/{$in->id}/present", ['present' => true, 'revision' => $in->revision])->assertSessionHas('error', '1PM is closed.');
            actingAs($who)->put("/{$session->code}/squads/{$in->squad_id}/head-count", ['count' => 1])->assertSessionHas('error', '1PM is closed.');
            actingAs($who)->get("/{$session->code}/whos-in")->assertOk();
        }

        expect([$in->fresh()?->isIn(), $in->fresh()?->present_at, CheckIn::query()->count()])->toBe([true, null, 1]);
    });
});
