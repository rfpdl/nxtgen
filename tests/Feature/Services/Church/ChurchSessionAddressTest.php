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
use function Pest\Laravel\get;
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
 * The Session's own address (spec item 16): opening a class gives it a code, and its door is `/<code>/checkin`, its
 * Who's in `/<code>/whos-in`. A Role at the Session's satellite is needed, else "not found". A closed Session's Who's
 * in stays readable; nothing changes there.
 *
 * It is Saturday 31 Oct 2026, 09:00 in Dubai, throughout.
 */

beforeEach(fn () => travelTo(CarbonImmutable::parse('2026-10-31 09:00:00', 'Asia/Dubai')));

/** @return array{dxb: Satellite, house: Satellite, onePm: Schedule, session: Session} */
function addressChurch(): array
{
    $church = church(['slug' => 'ccf', 'timezone' => 'Asia/Dubai']);
    $dxb = Satellite::factory()->for($church)->withSquads()->create(['name' => 'CCF DXB', 'slug' => 'dxb']);
    $house = Satellite::factory()->for($church)->withSquads()->create(['name' => 'House Church', 'slug' => 'house']);
    $onePm = scheduleAt($dxb, ['name' => '1PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '13:00', 'ends_at' => '15:00']);

    return ['dxb' => $dxb, 'house' => $house, 'onePm' => $onePm, 'session' => sessionOf($onePm)];
}

it('opens the door and Who\'s in at the Session\'s code for staff of its satellite @story#206', function (): void {
    ['dxb' => $dxb, 'session' => $session] = addressChurch();

    foreach ([staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)), staffMember(RoleEnum::Admin, atSatellite($dxb))] as $staff) {
        actingAs($staff)->get("/{$session->code}/checkin")->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Church/Door/Index')
            ->where('session.code', $session->code)
            ->where('session.name', '1PM')
            ->where('session.state', 'open')
            ->where('satellite.slug', 'dxb'));
        actingAs($staff)->get("/{$session->code}/whos-in")->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Church/Door/WhosIn')
            ->where('session.code', $session->code));
    }
});

it('opens it for the class\'s own class admin, not another class\'s @story#206 @story#207', function (): void {
    ['dxb' => $dxb, 'onePm' => $onePm, 'session' => $session] = addressChurch();
    $sevenPm = scheduleAt($dxb, ['name' => '7PM', 'starts_at' => '19:00', 'ends_at' => '21:00']);

    actingAs(classAdminOf($onePm))->get("/{$session->code}/checkin")->assertOk();
    actingAs(classAdminOf($sevenPm))->get("/{$session->code}/checkin")->assertNotFound();
});

it('is not found for staff of another satellite, someone with no Role, a code nobody has — and asks a stranger to sign in @story#207', function (): void {
    ['house' => $house, 'session' => $session] = addressChurch();

    actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))->get("/{$session->code}/checkin")->assertNotFound();
    actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))->get("/{$session->code}/whos-in")->assertNotFound();
    actingAs(User::factory()->create())->get("/{$session->code}/checkin")->assertNotFound();
    actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))->get('/zzzzzzzz/checkin')->assertNotFound();
    actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($house)))->get("/{$session->code}/whos-in")->assertNotFound();
    auth()->logout();
    get("/{$session->code}/checkin")->assertRedirect('/login');
    get("/{$session->code}/whos-in")->assertRedirect('/login');
});

it('keeps a closed Session\'s Who\'s in readable, and refuses a check-in there @story#206 @story#207', function (): void {
    ['dxb' => $dxb, 'session' => $session] = addressChurch();
    $lifeshaper = staffMember(RoleEnum::Lifeshaper, atSatellite($dxb));
    checkedIn($session, childOf(familyAt($dxb), ['level' => 2, 'gender' => 'girl']));
    $session->update(['closed_at' => now()]);
    $amara = childOf(familyAt($dxb), ['level' => 1, 'gender' => 'boy']);

    actingAs($lifeshaper)->get("/{$session->code}/whos-in")->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->where('session.state', 'closed')->has('squads', 1));
    actingAs($lifeshaper)->post("/{$session->code}/check-ins", ['children' => [['child_id' => $amara->id, 'level' => null, 'make_active' => false]]])
        ->assertSessionHas('error', '1PM is closed.');

    expect(CheckIn::query()->where('child_id', $amara->id)->exists())->toBeFalse();
});

it('keeps the code when the class is opened again the same day — a link already shared still works @story#206', function (): void {
    ['dxb' => $dxb, 'onePm' => $onePm, 'session' => $session] = addressChurch();
    $admin = staffMember(RoleEnum::Admin, atSatellite($dxb));
    $session->update(['closed_at' => now()]);

    actingAs($admin)->post("/dxb/serve/classes/{$onePm->id}/session")->assertRedirect("/{$session->code}/checkin");

    expect($session->fresh()?->code)->toBe($session->code);
    actingAs($admin)->get("/{$session->code}/checkin")->assertInertia(fn (AssertableInertia $page) => $page->where('session.state', 'open'));
});
