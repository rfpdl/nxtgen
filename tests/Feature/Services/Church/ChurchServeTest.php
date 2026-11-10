<?php

declare(strict_types=1);

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
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;
use function Tests\Support\staffMember;

/*
 * Serve's first page (spec item 17): today's classes at the satellite in the address that the person's Role covers —
 * the open ones a link to their door, the others "Not open yet" (Roy 2026-11-10: a Lifeshaper sees them too, with no
 * Open button; an admin opens one from here) or closed. None today: "No class today" (the page says it).
 *
 * It is Saturday 31 Oct 2026, 09:00 in Dubai, throughout.
 */

beforeEach(fn () => travelTo(CarbonImmutable::parse('2026-10-31 09:00:00', 'Asia/Dubai')));

/** @return array{dxb: Satellite, house: Satellite, onePm: Schedule, sevenPm: Schedule} */
function serveChurch(): array
{
    $church = church(['slug' => 'ccf', 'timezone' => 'Asia/Dubai']);
    $dxb = Satellite::factory()->for($church)->withSquads()->create(['name' => 'CCF DXB', 'slug' => 'dxb']);
    $house = Satellite::factory()->for($church)->withSquads()->create(['name' => 'House Church', 'slug' => 'house']);
    $onePm = scheduleAt($dxb, ['name' => '1PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '13:00', 'ends_at' => '15:00']);
    $sevenPm = scheduleAt($dxb, ['name' => '7PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '19:00', 'ends_at' => '21:00']);
    scheduleAt($dxb, ['name' => 'Sunday school', 'weekday' => WeekdayEnum::Sunday]);
    scheduleAt($dxb, ['name' => 'Retired', 'is_active' => false]);
    scheduleAt($house, ['name' => '10AM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '10:00']);

    return ['dxb' => $dxb, 'house' => $house, 'onePm' => $onePm, 'sevenPm' => $sevenPm];
}

it('shows an admin today\'s classes — an open one with its door\'s code, one not open yet with Open @story#208', function (): void {
    ['dxb' => $dxb, 'onePm' => $onePm] = serveChurch();
    $session = sessionOf($onePm);

    actingAs(staffMember(RoleEnum::Admin, atSatellite($dxb)))->get('/dxb/serve')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Church/Serve/Index')
        ->where('today', 'Sat 31 Oct')
        ->has('classes', 2)
        ->where('classes.0', ['id' => $onePm->id, 'name' => '1PM', 'times' => '13:00–15:00', 'state' => 'open', 'state_label' => 'Open', 'state_color' => 'success', 'code' => $session->code, 'can_open' => true])
        ->where('classes.1.name', '7PM')
        ->where('classes.1.state', 'not_open')
        ->where('classes.1.code', null)
        ->where('classes.1.can_open', true));
});

it('shows a Lifeshaper the classes not open yet too, with no Open — and a closed one as closed @story#208 @story#209', function (): void {
    ['dxb' => $dxb, 'onePm' => $onePm] = serveChurch();
    sessionOf($onePm)->update(['closed_at' => now()]);

    actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)))->get('/dxb/serve')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('classes.0.state', 'closed')
        ->where('classes.0.can_open', false)
        ->where('classes.1.state', 'not_open')
        ->where('classes.1.can_open', false));
});

it('shows a class admin only their own class, which they may open @story#208 @story#209', function (): void {
    ['sevenPm' => $sevenPm] = serveChurch();

    actingAs(classAdminOf($sevenPm))->get('/dxb/serve')->assertInertia(fn (AssertableInertia $page) => $page
        ->has('classes', 1)
        ->where('classes.0.name', '7PM')
        ->where('classes.0.can_open', true));
});

it('says there is no class on a day none meets @story#208', function (): void {
    ['dxb' => $dxb] = serveChurch();
    travelTo(CarbonImmutable::parse('2026-11-02 09:00:00', 'Asia/Dubai'));   // a Monday

    actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)))->get('/dxb/serve')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('today', 'Mon 2 Nov')->where('classes', []));
});

it('shows each satellite its own classes; staff of another satellite and someone with no Role are not found; a stranger signs in @story#209', function (): void {
    ['dxb' => $dxb, 'house' => $house] = serveChurch();
    $both = staffMember(RoleEnum::Lifeshaper, atSatellite($dxb));
    grantRole($both, RoleEnum::Lifeshaper, atSatellite($house));

    actingAs($both)->get('/house/serve')->assertInertia(fn (AssertableInertia $page) => $page->has('classes', 1)->where('classes.0.name', '10AM'));
    actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))->get('/dxb/serve')->assertNotFound();
    actingAs(User::factory()->create())->get('/dxb/serve')->assertNotFound();
    auth()->logout();
    get('/dxb/serve')->assertRedirect('/login');
});

it('lists, on a class\'s door and Who\'s in, the satellite\'s classes open now that the person may open — for the bar (Roy 2026-11-10) @story#210', function (): void {
    ['dxb' => $dxb, 'onePm' => $onePm, 'sevenPm' => $sevenPm] = serveChurch();
    $one = sessionOf($onePm);
    $seven = sessionOf($sevenPm);
    sessionOf(Schedule::query()->where('name', '10AM')->sole());   // another satellite's

    actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)))->get("/{$one->code}/checkin")->assertInertia(fn (AssertableInertia $page) => $page
        ->where('open_classes', [['code' => $one->code, 'name' => '1PM'], ['code' => $seven->code, 'name' => '7PM']]));
    actingAs(staffMember(RoleEnum::Admin, atSatellite($dxb)))->get("/{$one->code}/whos-in")->assertInertia(fn (AssertableInertia $page) => $page
        ->where('open_classes', [['code' => $one->code, 'name' => '1PM'], ['code' => $seven->code, 'name' => '7PM']]));
    // A class admin opens only their own class's door, so only it is listed.
    actingAs(classAdminOf($onePm))->get("/{$one->code}/checkin")->assertInertia(fn (AssertableInertia $page) => $page
        ->where('open_classes', [['code' => $one->code, 'name' => '1PM']]));
    // From a closed class, the one still open is there to jump to (Astra).
    $one->update(['closed_at' => now()]);
    actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)))->get("/{$one->code}/whos-in")->assertInertia(fn (AssertableInertia $page) => $page
        ->where('session.state', 'closed')
        ->where('open_classes', [['code' => $seven->code, 'name' => '7PM']]));
});
