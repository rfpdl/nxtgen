<?php

declare(strict_types=1);

use App\Data\Models\CheckIn;
use App\Data\Models\Child;
use App\Data\Models\Satellite;
use App\Data\Models\Schedule;
use App\Data\Models\User;
use App\Enums\GenderEnum;
use App\Enums\RoleEnum;
use App\Enums\WeekdayEnum;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\travelTo;
use function Tests\Support\atChurch;
use function Tests\Support\atSatellite;
use function Tests\Support\checkedIn;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\guardianOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;
use function Tests\Support\staffMember;

/*
 * Who's in, the door's second tab (DECISIONS 2026-10-28; GLOSSARY "Roll call", "Squad leader"): the children checked
 * in to today's class by Squad, and a tap marks a child present. Same class picker, day and people as the door; the
 * tap has the same access as Undo. AccessScope::canCheckInAt owns who may; these prove the screens ask it.
 *
 * It is Saturday 31 Oct 2026, 09:00 in Dubai, throughout.
 */

beforeEach(function (): void {
    travelTo(CarbonImmutable::parse('2026-10-31 09:00:00', 'Asia/Dubai'));
});

/** @return array{dxb: Satellite, house: Satellite, onePm: Schedule, lifeshaper: User, amara: Child, sofia: Child, isabel: Child, ben: Child, kai: Child, bea: Child, visitor: Child} */
function whosInChurch(): array
{
    $church = church(['slug' => 'ccf', 'name' => 'CCF', 'timezone' => 'Asia/Dubai']);
    $dxb = Satellite::factory()->for($church)->withSquads()->create(['name' => 'CCF DXB']);
    $house = Satellite::factory()->for($church)->withSquads()->create(['name' => 'House Church']);
    $onePm = scheduleAt($dxb, ['name' => '1PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '13:00', 'ends_at' => '15:00']);
    scheduleAt($dxb, ['name' => '7PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '19:00']);
    scheduleAt($dxb, ['name' => 'Sunday school', 'weekday' => WeekdayEnum::Sunday]);
    scheduleAt($house, ['name' => '10AM', 'weekday' => WeekdayEnum::Saturday]);

    $cruz = familyAt($dxb);
    guardianOf($cruz, User::factory()->create(), ['first_name' => 'Maria', 'last_name' => 'Cruz', 'phone' => '0501234567']);
    $girl = fn (string $nickname, int $level, ?string $allergies = null): Child => childOf($cruz, ['nickname' => $nickname, 'gender' => GenderEnum::Girl, 'level' => $level, 'allergies' => $allergies, 'illnesses' => $allergies === null ? null : 'Asthma']);

    return [
        'dxb' => $dxb, 'house' => $house, 'onePm' => $onePm,
        'lifeshaper' => staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)),
        'amara' => $girl('Amara', 2, 'Peanuts'),
        'sofia' => $girl('Sofia', 2),
        'isabel' => $girl('Isabel', 2),
        'ben' => childOf($cruz, ['nickname' => 'Ben', 'gender' => GenderEnum::Boy, 'level' => 2]),
        'kai' => childOf($cruz, ['nickname' => 'Kai', 'gender' => GenderEnum::Boy, 'level' => 1]),
        'bea' => $girl('Bea', 3),
        'visitor' => childOf(familyAt($house), ['nickname' => 'Pia', 'gender' => GenderEnum::Girl, 'level' => 2]),
    ];
}

describe('the Who\'s in page', function (): void {
    it('lists today\'s class by Squad in the Attendance order — each with how many are present — children by nickname @story#117', function (): void {
        $w = whosInChurch();
        $session = sessionOf($w['onePm']);
        $sofia = checkedIn($session, $w['sofia'], ['present_at' => now(), 'present_by' => $w['lifeshaper']->id]);
        $amara = checkedIn($session, $w['amara']);
        $bea = checkedIn($session, $w['bea']);
        $kai = checkedIn($session, $w['kai'], ['present_at' => now()]);
        $ben = checkedIn($session, $w['ben']);
        checkedIn($session, $w['isabel'], ['undone_at' => now(), 'present_at' => now()]);
        checkedIn(sessionOf(Schedule::query()->where('name', '7PM')->sole()), $w['visitor']);

        actingAs($w['lifeshaper'])->get('/ccf/door/whos-in')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Church/Door/WhosIn')
            ->where('today', 'Sat 31 Oct')
            ->has('classes', 2)
            ->where('class_id', $w['onePm']->id)
            ->where('squad_id', null)
            ->where('squads', [
                ['id' => $kai->squad_id, 'name' => 'Level 1', 'level' => ['number' => 1, 'name' => 'Level 1'], 'present' => 1, 'count' => 1, 'children' => [
                    ['id' => $kai->id, 'nickname' => 'Kai', 'has_allergies' => false, 'present' => true],
                ]],
                ['id' => $ben->squad_id, 'name' => 'Level 2 Boys', 'level' => ['number' => 2, 'name' => 'Level 2'], 'present' => 0, 'count' => 1, 'children' => [
                    ['id' => $ben->id, 'nickname' => 'Ben', 'has_allergies' => false, 'present' => false],
                ]],
                ['id' => $amara->squad_id, 'name' => 'Level 2 Girls', 'level' => ['number' => 2, 'name' => 'Level 2'], 'present' => 1, 'count' => 2, 'children' => [
                    ['id' => $amara->id, 'nickname' => 'Amara', 'has_allergies' => true, 'present' => false],
                    ['id' => $sofia->id, 'nickname' => 'Sofia', 'has_allergies' => false, 'present' => true],
                ]],
                ['id' => $bea->squad_id, 'name' => 'Level 3 Girls', 'level' => ['number' => 3, 'name' => 'Level 3'], 'present' => 0, 'count' => 1, 'children' => [
                    ['id' => $bea->id, 'nickname' => 'Bea', 'has_allergies' => false, 'present' => false],
                ]],
            ]));
    });

    it('keeps the Squad picked with ?squad=, and falls back to All when that Squad has no one in @story#117', function (): void {
        $w = whosInChurch();
        $session = sessionOf($w['onePm']);
        $amara = checkedIn($session, $w['amara']);
        $kai = checkedIn($session, $w['kai']);

        actingAs($w['lifeshaper'])->get("/ccf/door/whos-in?class={$w['onePm']->id}&squad={$amara->squad_id}")->assertInertia(fn (AssertableInertia $page) => $page
            ->where('squad_id', $amara->squad_id)->has('squads', 2));

        $kai->forceFill(['undone_at' => now()])->save();
        actingAs($w['lifeshaper'])->get("/ccf/door/whos-in?class={$w['onePm']->id}&squad={$kai->squad_id}")->assertInertia(fn (AssertableInertia $page) => $page
            ->where('squad_id', null)->has('squads', 1));
    });

    it('shows no Squads before the first Check-in of the class @story#117', function (): void {
        $w = whosInChurch();

        actingAs($w['lifeshaper'])->get('/ccf/door/whos-in')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('class_id', $w['onePm']->id)->where('squads', [])->where('squad_id', null));
    });

    it('gives a church-wide Role every satellite\'s classes, and says when none meets today @story#117', function (): void {
        $w = whosInChurch();
        $everywhere = staffMember(RoleEnum::Lifeshaper, atChurch($w['dxb']->church));

        actingAs($everywhere)->get('/ccf/door/whos-in')->assertInertia(fn (AssertableInertia $page) => $page->has('classes', 3));

        travelTo(CarbonImmutable::parse('2026-11-02 09:00:00', 'Asia/Dubai'));   // a Monday
        actingAs($w['lifeshaper'])->get('/ccf/door/whos-in')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('today', 'Mon 2 Nov')->where('classes', [])->where('class_id', null)->where('squads', []));
    });

    it('refuses a class at a satellite the Role does not cover, another church\'s, or not meeting today @story#118', function (): void {
        $w = whosInChurch();
        $sunday = Schedule::query()->where('name', 'Sunday school')->sole();
        $theirs = scheduleAt(Satellite::factory()->for(church(['slug' => 'other']))->create());
        $houseClass = Schedule::query()->where('satellite_id', $w['house']->id)->sole();

        foreach ([$houseClass, $theirs, $sunday] as $class) {
            actingAs($w['lifeshaper'])->get("/ccf/door/whos-in?class={$class->id}")->assertNotFound();
        }
    });

    it('never sends a photo, a phone number or a health detail @story#118', function (): void {
        $w = whosInChurch();
        checkedIn(sessionOf($w['onePm']), $w['amara']);

        $json = (string) json_encode(actingAs($w['lifeshaper'])->get('/ccf/door/whos-in')->viewData('page'));

        expect(preg_match('/Peanuts|Asthma|0501234567|photo/', $json))->toBe(0)
            ->and($json)->toContain('Amara');
    });

    it('is not there for a parent or someone signed out @story#118', function (): void {
        whosInChurch();
        $parent = User::query()->whereHas('guardians')->firstOrFail();

        actingAs($parent)->get('/ccf/door/whos-in')->assertNotFound();
        auth()->logout();
        get('/ccf/door/whos-in')->assertRedirect('/login');
    });
});

describe('marking a child present', function (): void {
    it('marks a child present with who and when, saying nothing — the tick says it — and a second tap unmarks them @story#119', function (): void {
        $w = whosInChurch();
        $in = checkedIn(sessionOf($w['onePm']), $w['amara']);

        actingAs($w['lifeshaper'])->put("/ccf/check-ins/{$in->id}/present", ['present' => true])
            ->assertRedirect()->assertSessionMissing('success')->assertSessionMissing('error');
        expect([$in->fresh()?->isPresent(), $in->fresh()?->present_by, $in->fresh()?->present_at?->toDateTimeString()])
            ->toBe([true, $w['lifeshaper']->id, '2026-10-31 05:00:00']);

        actingAs($w['lifeshaper'])->put("/ccf/check-ins/{$in->id}/present", ['present' => false])
            ->assertRedirect()->assertSessionMissing('success');
        expect([$in->fresh()?->isPresent(), $in->fresh()?->present_by])->toBe([false, null]);
    });

    it('drops the mark when the door undoes the Check-in, and a re-check-in starts unmarked @story#119', function (): void {
        $w = whosInChurch();
        $in = checkedIn(sessionOf($w['onePm']), $w['amara']);
        actingAs($w['lifeshaper'])->put("/ccf/check-ins/{$in->id}/present", ['present' => true]);

        actingAs($w['lifeshaper'])->put("/ccf/check-ins/{$in->id}/undo");
        actingAs($w['lifeshaper'])->get('/ccf/door/whos-in')->assertInertia(fn (AssertableInertia $page) => $page->where('squads', []));

        actingAs($w['lifeshaper'])->post("/ccf/classes/{$w['onePm']->id}/check-ins", ['children' => [['child_id' => $w['amara']->id, 'level' => null, 'make_active' => false]]]);
        actingAs($w['lifeshaper'])->get('/ccf/door/whos-in')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('squads.0.present', 0)->where('squads.0.children.0.present', false));
    });

    it('refuses a child whose Check-in was undone, saying why, and marks nothing @story#120', function (): void {
        $w = whosInChurch();
        $in = checkedIn(sessionOf($w['onePm']), $w['amara'], ['undone_at' => now()]);

        actingAs($w['lifeshaper'])->put("/ccf/check-ins/{$in->id}/present", ['present' => true])
            ->assertRedirect()->assertSessionHas('error', 'Amara is not checked in any more.');
        expect($in->fresh()?->isPresent())->toBeFalse();
    });

    it('marks only at a satellite the Role covers, only today, in this church, and needs a yes or no @story#120', function (): void {
        $w = whosInChurch();
        $atHouse = checkedIn(sessionOf(Schedule::query()->where('satellite_id', $w['house']->id)->sole()), $w['visitor']);
        $lastWeek = checkedIn(sessionOf($w['onePm'], '2026-10-24'), $w['amara']);
        $today = checkedIn(sessionOf($w['onePm']), $w['sofia']);

        actingAs($w['lifeshaper'])->put("/ccf/check-ins/{$atHouse->id}/present", ['present' => true])->assertNotFound();
        actingAs($w['lifeshaper'])->put("/ccf/check-ins/{$lastWeek->id}/present", ['present' => true])->assertNotFound();
        actingAs(staffMember(RoleEnum::Admin, atChurch(church(['slug' => 'other']))))->put("/other/check-ins/{$today->id}/present", ['present' => true])->assertNotFound();
        actingAs($w['lifeshaper'])->put("/ccf/check-ins/{$today->id}/present", [])->assertSessionHasErrors('present');

        expect(CheckIn::query()->whereNotNull('present_at')->count())->toBe(0);
    });
});
