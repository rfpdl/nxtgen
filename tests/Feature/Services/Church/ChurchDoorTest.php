<?php

declare(strict_types=1);

use App\Data\Models\CheckIn;
use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Data\Models\Note;
use App\Data\Models\Satellite;
use App\Data\Models\Schedule;
use App\Data\Models\Session;
use App\Data\Models\User;
use App\Enums\ChildStatusEnum;
use App\Enums\GenderEnum;
use App\Enums\RoleEnum;
use App\Enums\WeekdayEnum;
use App\Http\Middleware\HandleInertiaRequests;
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
 * Check in at the door (DECISIONS 2026-10-26; GLOSSARY "Session", "Check-in"): a Lifeshaper picks today's class,
 * searches the whole church, ticks a family's children and checks them in. The first Check-in makes the Session; each
 * child joins THIS satellite's Squad for their Level and gender — a visiting child too. Inactive → made active again
 * first; Graduated → refused; no Level → one picked at the door. AccessScope::canCheckInAt owns who may; these prove
 * the screens ask it.
 *
 * It is Saturday 31 Oct 2026, 09:00 in Dubai, throughout.
 */

beforeEach(function (): void {
    travelTo(CarbonImmutable::parse('2026-10-31 09:00:00', 'Asia/Dubai'));
});

/** @return array{church: Church, dxb: Satellite, house: Satellite, onePm: Schedule, lifeshaper: User, amara: Child, ben: Child, noah: Child, lola: Child, visitor: Child} */
function doorChurch(): array
{
    $church = church(['slug' => 'ccf', 'name' => 'CCF', 'timezone' => 'Asia/Dubai', 'moving_up_reference_date' => '2027-02-01']);
    $dxb = Satellite::factory()->for($church)->withSquads()->create(['name' => 'CCF DXB']);
    $house = Satellite::factory()->for($church)->withSquads()->create(['name' => 'House Church']);
    $onePm = scheduleAt($dxb, ['name' => '1PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '13:00', 'ends_at' => '15:00']);
    scheduleAt($dxb, ['name' => '7PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '19:00', 'ends_at' => '21:00']);
    scheduleAt($dxb, ['name' => 'Sunday school', 'weekday' => WeekdayEnum::Sunday]);
    scheduleAt($house, ['name' => '10AM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '10:00']);

    $cruz = familyAt($dxb);
    guardianOf($cruz, User::factory()->create(), ['first_name' => 'Maria', 'last_name' => 'Cruz', 'phone' => '0501234567']);
    $santos = familyAt($house);
    guardianOf($santos, User::factory()->create(), ['first_name' => 'Grace', 'last_name' => 'Santos']);

    return [
        'church' => $church, 'dxb' => $dxb, 'house' => $house, 'onePm' => $onePm,
        'lifeshaper' => staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)),
        'amara' => childOf($cruz, ['nickname' => 'Amara', 'first_name' => 'Amara', 'last_name' => 'Cruz', 'gender' => GenderEnum::Girl, 'level' => 2, 'date_of_birth' => '2020-03-01', 'allergies' => 'Peanuts', 'illnesses' => 'Asthma']),
        'ben' => childOf($cruz, ['nickname' => 'Ben', 'first_name' => 'Benjamin', 'last_name' => 'Cruz', 'gender' => GenderEnum::Boy, 'level' => 1, 'date_of_birth' => '2022-01-10', 'allergies' => null]),
        'noah' => childOf($cruz, ['nickname' => 'Noah', 'first_name' => 'Noah', 'last_name' => 'Cruz', 'gender' => GenderEnum::Boy, 'level' => 3, 'status' => ChildStatusEnum::Inactive, 'date_of_birth' => '2018-05-05']),
        'lola' => childOf($cruz, ['nickname' => 'Lola', 'first_name' => 'Dolores', 'last_name' => 'Cruz', 'gender' => GenderEnum::Girl, 'level' => null, 'date_of_birth' => '2018-06-01']),
        'visitor' => childOf($santos, ['nickname' => 'Bea', 'first_name' => 'Bea', 'last_name' => 'Santos', 'gender' => GenderEnum::Girl, 'level' => 3, 'date_of_birth' => '2018-07-02']),
    ];
}

/**
 * The check-in form's body: each child with an optional Level picked at the door and "make active again".
 *
 * @param  list<array{0: Child, 1?: int|null, 2?: bool}>  $children
 * @return array{children: list<array{child_id: int, level: int|null, make_active: bool}>}
 */
function ticked(array $children): array
{
    return ['children' => array_map(fn (array $c): array => ['child_id' => $c[0]->id, 'level' => $c[1] ?? null, 'make_active' => $c[2] ?? false], $children)];
}

describe('the door page', function (): void {
    it('shows today\'s date, today\'s classes at the satellites the Lifeshaper\'s Role covers, and the count checked in @story#103', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'amara' => $amara, 'ben' => $ben] = doorChurch();
        $session = sessionOf($onePm);
        checkedIn($session, $amara);
        checkedIn($session, $ben, ['undone_at' => now()]);

        actingAs($lifeshaper)->get('/ccf/door')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Church/Door/Index')
            ->where('today', 'Sat 31 Oct')
            ->has('classes', 2)
            ->where('classes.0.name', '1PM')
            ->where('classes.0.satellite', 'CCF DXB')
            ->where('classes.0.times', '13:00–15:00')
            ->where('classes.1.name', '7PM')
            ->where('class_id', $onePm->id)
            ->where('checked_in', 1)
            ->where('families', []));
    });

    it('gives a church-wide Role every satellite\'s classes for today @story#103', function (): void {
        ['church' => $church] = doorChurch();

        actingAs(staffMember(RoleEnum::Lifeshaper, atChurch($church)))->get('/ccf/door')->assertInertia(fn (AssertableInertia $page) => $page
            ->has('classes', 3)
            ->where('classes.0.satellite', 'CCF DXB')
            ->where('classes.2.name', '10AM'));
    });

    it('finds a family church-wide by the parent\'s name, each child with Level, Squad, allergy flag and who is in @story#103', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'amara' => $amara, 'ben' => $ben, 'lola' => $lola] = doorChurch();
        $in = checkedIn(sessionOf($onePm), $ben);

        actingAs($lifeshaper)->get("/ccf/door?class={$onePm->id}&q=Maria")->assertInertia(fn (AssertableInertia $page) => $page
            ->where('query', 'Maria')
            ->has('families', 1)
            ->where('families.0.name', 'Cruz family')
            ->where('families.0.primary_name', 'Maria Cruz')
            ->where('families.0.satellite', 'CCF DXB')
            ->has('families.0.children', 4)
            ->where('families.0.children.0.nickname', 'Amara')
            ->where('families.0.children.0.squad', 'Level 2 Girls')
            ->where('families.0.children.0.has_allergies', true)
            ->where('families.0.children.0.check_in_id', null)
            ->where('families.0.children.1.nickname', 'Ben')
            ->where('families.0.children.1.squad', 'Level 1')
            ->where('families.0.children.1.check_in_id', $in->id)
            ->where('families.0.children.2.status', 'inactive')
            ->where('families.0.children.3.nickname', 'Lola')
            ->where('families.0.children.3.level', null)
            ->where('families.0.children.3.suggested_level', 3)
            ->has('levels', 4));
        expect([$amara->level, $lola->level])->toBe([2, null]);
    });

    it('finds a visiting family from another satellite, in this satellite\'s Squads @story#103', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper] = doorChurch();

        actingAs($lifeshaper)->get("/ccf/door?class={$onePm->id}&q=Santos")->assertInertia(fn (AssertableInertia $page) => $page
            ->where('families.0.satellite', 'House Church')
            ->where('families.0.children.0.nickname', 'Bea')
            ->where('families.0.children.0.squad', 'Level 3 Girls'));
    });

    it('shows a child\'s health details only when their name is tapped @story#105', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'amara' => $amara] = doorChurch();

        actingAs($lifeshaper)->get("/ccf/door?class={$onePm->id}&q=Maria")
            ->assertInertia(fn (AssertableInertia $page) => $page->missing('health'));

        actingAs($lifeshaper)->get("/ccf/door?class={$onePm->id}&q=Maria&health={$amara->id}", [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request()),
            'X-Inertia-Partial-Component' => 'Church/Door/Index',
            'X-Inertia-Partial-Data' => 'health',
        ])->assertOk()->assertJsonPath('props.health.nickname', 'Amara')
            ->assertJsonPath('props.health.allergies', 'Peanuts')
            ->assertJsonPath('props.health.illnesses', 'Asthma');
    });

    it('says when there is no class today, and shows no class meeting on another day @story#104', function (): void {
        ['lifeshaper' => $lifeshaper] = doorChurch();
        travelTo(CarbonImmutable::parse('2026-11-02 09:00:00', 'Asia/Dubai'));   // a Monday

        actingAs($lifeshaper)->get('/ccf/door')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('today', 'Mon 2 Nov')->where('classes', [])->where('class_id', null)->where('checked_in', 0));
    });

    it('refuses a class at a satellite the Role does not cover, another church\'s, or not meeting today @story#104', function (): void {
        ['house' => $house, 'lifeshaper' => $lifeshaper] = doorChurch();
        $sunday = Schedule::query()->where('name', 'Sunday school')->sole();
        $theirs = scheduleAt(Satellite::factory()->for(church(['slug' => 'other']))->create());
        $houseClass = Schedule::query()->where('satellite_id', $house->id)->sole();

        foreach ([$houseClass, $theirs, $sunday] as $class) {
            actingAs($lifeshaper)->get("/ccf/door?class={$class->id}")->assertNotFound();
        }
    });

    it('never sends a photo, a phone number or a health detail with the search @story#104', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper] = doorChurch();

        $json = (string) json_encode(actingAs($lifeshaper)->get("/ccf/door?class={$onePm->id}&q=Maria")->viewData('page'));

        expect(preg_match('/Peanuts|Asthma|0501234567|photo/', $json))->toBe(0);
    });

    it('is not there for a parent or someone signed out @story#104', function (): void {
        doorChurch();
        $parent = User::query()->whereHas('guardians')->firstOrFail();

        actingAs($parent)->get('/ccf/door')->assertNotFound();
        auth()->logout();
        get('/ccf/door')->assertRedirect('/login');
    });
});

describe('checking children in', function (): void {
    it('checks a family\'s children in to today\'s class, making the Session, each in this satellite\'s Squad @story#106', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'amara' => $amara, 'ben' => $ben] = doorChurch();

        actingAs($lifeshaper)->post("/ccf/classes/{$onePm->id}/check-ins", ticked([[$amara], [$ben]]))
            ->assertRedirect()->assertSessionHas('success', 'Checked in Amara and Ben.')->assertSessionMissing('error');

        $session = Session::query()->sole();
        expect([$session->schedule_id, $session->date->toDateString()])->toBe([$onePm->id, '2026-10-31'])
            ->and(CheckIn::query()->with('squad')->orderBy('id')->get()->map(fn (CheckIn $c): array => [$c->child_id, $c->squad->name(), $c->checked_in_by])->all())
            ->toBe([[$amara->id, 'Level 2 Girls', $lifeshaper->id], [$ben->id, 'Level 1', $lifeshaper->id]]);
    });

    it('checks a visiting child from another satellite in, in THIS satellite\'s Squad @story#106', function (): void {
        ['dxb' => $dxb, 'onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'visitor' => $visitor] = doorChurch();

        actingAs($lifeshaper)->post("/ccf/classes/{$onePm->id}/check-ins", ticked([[$visitor]]))->assertSessionHas('success', 'Checked in Bea.');

        $in = CheckIn::query()->with('squad')->sole();
        expect([$in->squad->satellite_id, $in->squad->name()])->toBe([$dxb->id, 'Level 3 Girls']);
    });

    it('says a child already in is already in, and makes no second Check-in @story#106', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'amara' => $amara] = doorChurch();
        checkedIn(sessionOf($onePm), $amara);

        actingAs($lifeshaper)->post("/ccf/classes/{$onePm->id}/check-ins", ticked([[$amara]]))->assertSessionHas('success', 'Amara was already checked in.');

        expect(CheckIn::query()->count())->toBe(1);
    });

    it('makes an inactive child active again, noted, and checks them in @story#107', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'noah' => $noah] = doorChurch();

        actingAs($lifeshaper)->post("/ccf/classes/{$onePm->id}/check-ins", ticked([[$noah, null, true]]))->assertSessionHas('success', 'Checked in Noah.');

        expect($noah->fresh()?->status)->toBe(ChildStatusEnum::Active)
            ->and(Note::query()->where('noteable_id', $noah->id)->sole()->body)->toBe('Noah: status changed from Inactive to Active.')
            ->and(CheckIn::query()->sole()->child_id)->toBe($noah->id);
    });

    it('saves the Level picked at the door for a child with none, notes it, and checks them in @story#107', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'lola' => $lola] = doorChurch();

        actingAs($lifeshaper)->post("/ccf/classes/{$onePm->id}/check-ins", ticked([[$lola, 3]]))->assertSessionHas('success', 'Checked in Lola.');

        $child = $lola->fresh();
        expect([$child?->level, $child?->level_set_by])->toBe([3, $lifeshaper->id])
            ->and(Note::query()->where('noteable_id', $lola->id)->sole()->body)->toBe('Lola: Level changed from no Level to Level 3.')
            ->and(CheckIn::query()->with('squad')->sole()->squad->name())->toBe('Level 3 Girls');
    });

    it('never changes a Level the child already has at the door @story#107', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'amara' => $amara] = doorChurch();

        actingAs($lifeshaper)->post("/ccf/classes/{$onePm->id}/check-ins", ticked([[$amara, 4]]))->assertSessionHas('success');

        expect($amara->fresh()?->level)->toBe(2)->and(Note::query()->count())->toBe(0);
    });

    it('refuses a Graduated child, an inactive child not made active, and a child with no Level, saying why @story#108', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'amara' => $amara, 'noah' => $noah, 'lola' => $lola, 'ben' => $ben] = doorChurch();
        $ben->forceFill(['status' => ChildStatusEnum::Graduated])->save();

        actingAs($lifeshaper)->post("/ccf/classes/{$onePm->id}/check-ins", ticked([[$amara], [$ben, null, true], [$noah], [$lola]]))
            ->assertSessionHas('success', 'Checked in Amara.')
            ->assertSessionHas('error', 'Not checked in: Ben has Graduated from NxtGen; Noah is inactive — make them active again first; Lola needs a Level first.');

        expect(CheckIn::query()->pluck('child_id')->all())->toBe([$amara->id])
            ->and($noah->fresh()?->status)->toBe(ChildStatusEnum::Inactive)
            ->and($ben->fresh()?->status)->toBe(ChildStatusEnum::Graduated);
    });

    it('checks in only at a satellite the Role covers, a class meeting today, and this church\'s children @story#109', function (): void {
        ['house' => $house, 'onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'amara' => $amara] = doorChurch();
        $houseClass = Schedule::query()->where('satellite_id', $house->id)->sole();
        $sunday = Schedule::query()->where('name', 'Sunday school')->sole();
        $otherChild = childOf(familyAt(Satellite::factory()->for(church())->create()), ['level' => 1]);

        actingAs($lifeshaper)->post("/ccf/classes/{$houseClass->id}/check-ins", ticked([[$amara]]))->assertNotFound();
        actingAs($lifeshaper)->post("/ccf/classes/{$sunday->id}/check-ins", ticked([[$amara]]))->assertNotFound();
        actingAs($lifeshaper)->post("/ccf/classes/{$onePm->id}/check-ins", ticked([[$amara], [$otherChild]]))->assertNotFound();

        expect(CheckIn::query()->count())->toBe(0)->and(Session::query()->count())->toBe(0);
    });

    it('needs at least one ticked child, and a Level from the chart @story#109', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'lola' => $lola] = doorChurch();

        actingAs($lifeshaper)->post("/ccf/classes/{$onePm->id}/check-ins", ['children' => []])->assertSessionHasErrors('children');
        actingAs($lifeshaper)->post("/ccf/classes/{$onePm->id}/check-ins", ticked([[$lola, 9]]))->assertSessionHasErrors('children.0.level');
    });
});

describe('undoing a Check-in', function (): void {
    it('undoes a Check-in, and the child can be checked in again @story#110', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'ben' => $ben] = doorChurch();
        $in = checkedIn(sessionOf($onePm), $ben);

        actingAs($lifeshaper)->put("/ccf/check-ins/{$in->id}/undo")->assertRedirect()->assertSessionHas('success', 'Undid Ben\'s check-in.');
        expect([$in->fresh()?->isIn(), $in->fresh()?->undone_by])->toBe([false, $lifeshaper->id]);

        actingAs($lifeshaper)->post("/ccf/classes/{$onePm->id}/check-ins", ticked([[$ben]]))->assertSessionHas('success', 'Checked in Ben.');
        expect(CheckIn::query()->sole()->isIn())->toBeTrue();
    });

    it('undoes only at a satellite the Role covers, only today, in this church @story#111', function (): void {
        ['house' => $house, 'onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'visitor' => $visitor, 'amara' => $amara] = doorChurch();
        $atHouse = checkedIn(sessionOf(Schedule::query()->where('satellite_id', $house->id)->sole()), $visitor);
        $lastWeek = checkedIn(sessionOf($onePm, '2026-10-24'), $amara);

        actingAs($lifeshaper)->put("/ccf/check-ins/{$atHouse->id}/undo")->assertNotFound();
        actingAs($lifeshaper)->put("/ccf/check-ins/{$lastWeek->id}/undo")->assertNotFound();
        actingAs(staffMember(RoleEnum::Admin, atChurch(church(['slug' => 'other']))))->put("/other/check-ins/{$atHouse->id}/undo")->assertNotFound();

        expect([$atHouse->fresh()?->isIn(), $lastWeek->fresh()?->isIn()])->toBe([true, true]);
    });
});
