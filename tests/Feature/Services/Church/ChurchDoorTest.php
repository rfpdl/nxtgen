<?php

declare(strict_types=1);

use App\Data\Models\CheckIn;
use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Data\Models\Family;
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
use Illuminate\Support\Facades\DB;
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
use function Tests\Support\grantRole;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;
use function Tests\Support\staffMember;

/*
 * Check in at the door (DECISIONS 2026-10-26; GLOSSARY "Session", "Check-in"), at an opened class's own address
 * (spec item 16: `/<code>/checkin`): a Lifeshaper searches the whole church, ticks a family's children and checks them in — while an admin has the class open (spec
 * item 15); each child joins THIS satellite's Squad for their Level and gender — a visiting child too. Inactive → made active again
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
    $dxb = Satellite::factory()->for($church)->withSquads()->create(['name' => 'CCF DXB', 'slug' => 'dxb']);
    $house = Satellite::factory()->for($church)->withSquads()->create(['name' => 'House Church', 'slug' => 'house']);
    $onePm = scheduleAt($dxb, ['name' => '1PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '13:00', 'ends_at' => '15:00']);
    scheduleAt($dxb, ['name' => '7PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '19:00', 'ends_at' => '21:00']);
    scheduleAt($dxb, ['name' => 'Sunday school', 'weekday' => WeekdayEnum::Sunday]);
    scheduleAt($house, ['name' => '10AM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '10:00']);

    // An admin has opened today's 1PM (spec item 15): its door is at its code (spec item 16).
    sessionOf($onePm);

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

/** Today's 1PM Session, which an admin opened (doorChurch — spec item 15). */
function onePmSession(): Session
{
    return Session::query()->whereHas('schedule', fn ($q) => $q->where('name', '1PM'))->whereDate('date', '2026-10-31')->sole();
}

/** An address at 1PM's own door (spec item 16): `/<code>{$path}`. */
function door(string $path): string
{
    return '/'.onePmSession()->code.$path;
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
    it('shows the Session — its class, times, date and state — and the count checked in @story#103 @story#206', function (): void {
        ['lifeshaper' => $lifeshaper, 'amara' => $amara, 'ben' => $ben] = doorChurch();
        $session = onePmSession();
        checkedIn($session, $amara);
        checkedIn($session, $ben, ['undone_at' => now()]);

        actingAs($lifeshaper)->get(door('/checkin'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Church/Door/Index')
            ->where('session', ['code' => $session->code, 'class_id' => $session->schedule_id, 'name' => '1PM', 'times' => '13:00–15:00', 'date_label' => 'Sat 31 Oct', 'state' => 'open'])
            ->where('satellite.slug', 'dxb')
            ->has('checked_in', 1)
            ->where('families', []));
    });

    it('lists who is checked in to the chosen class today, newest first: nickname, Squad and the church\'s time @story#116', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'amara' => $amara, 'ben' => $ben, 'visitor' => $visitor] = doorChurch();
        $session = onePmSession();
        $amaraIn = checkedIn($session, $amara, ['checked_in_at' => CarbonImmutable::parse('2026-10-31 08:40:00', 'Asia/Dubai')->utc()]);
        $beaIn = checkedIn($session, $visitor, ['checked_in_at' => CarbonImmutable::parse('2026-10-31 08:55:00', 'Asia/Dubai')->utc()]);
        checkedIn($session, $ben, ['undone_at' => now()]);
        checkedIn(sessionOf(Schedule::query()->where('name', '7PM')->sole()), $ben);

        actingAs($lifeshaper)->get(door('/checkin'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('checked_in', [
                ['id' => $beaIn->id, 'nickname' => 'Bea', 'squad' => 'Level 3 Girls', 'level' => ['number' => 3, 'name' => 'Level 3'], 'checked_in_at' => '08:55'],
                ['id' => $amaraIn->id, 'nickname' => 'Amara', 'squad' => 'Level 2 Girls', 'level' => ['number' => 2, 'name' => 'Level 2'], 'checked_in_at' => '08:40'],
            ]));
    });

    it('asks the database the same number of times however many children are in @story#116', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'amara' => $amara] = doorChurch();
        $session = onePmSession();
        checkedIn($session, $amara);
        $count = function () use ($lifeshaper): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            actingAs($lifeshaper)->get(door('/checkin?q=Cruz'))->assertOk();
            DB::disableQueryLog();

            return count(DB::getQueryLog());
        };
        // The first visit also keeps the satellite as the viewer's last one (spec item 11): measure from the second.
        $count();
        $few = $count();

        $family = Family::query()->firstOrFail();
        foreach (['Ana', 'Bo', 'Cy', 'Di', 'Ed', 'Flo'] as $i => $nickname) {
            checkedIn($session, childOf($family, ['nickname' => $nickname, 'level' => 1 + $i % 4, 'gender' => $i % 2 === 0 ? GenderEnum::Boy : GenderEnum::Girl]));
        }

        expect($count())->toBe($few);
    });

    it('finds a family church-wide by the parent\'s name, each child with Level, Squad, allergy flag and who is in @story#103', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'amara' => $amara, 'ben' => $ben, 'lola' => $lola] = doorChurch();
        $in = checkedIn(onePmSession(), $ben);

        actingAs($lifeshaper)->get(door('/checkin?q=Maria'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('query', 'Maria')
            ->has('families', 1)
            ->where('families.0.name', 'Cruz family')
            ->where('families.0.primary_name', 'Maria Cruz')
            ->where('families.0.satellite', 'CCF DXB')
            ->has('families.0.children', 4)
            ->where('families.0.children.0.nickname', 'Amara')
            ->where('families.0.children.0.squad', 'Level 2 Girls')
            ->where('families.0.children.0.level', ['number' => 2, 'name' => 'Level 2'])
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

        actingAs($lifeshaper)->get(door('/checkin?q=Santos'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('families.0.satellite', 'House Church')
            ->where('families.0.children.0.nickname', 'Bea')
            ->where('families.0.children.0.squad', 'Level 3 Girls'));
    });

    it('says which found families the viewer may open — their own satellite\'s, not a visitor\'s @story#103', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'dxb' => $dxb, 'house' => $house] = doorChurch();

        actingAs($lifeshaper)->get(door('/checkin?q=Cruz'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('families.0.name', 'Cruz family')
            ->where('families.0.can_open', true));
        actingAs($lifeshaper)->get(door('/checkin?q=Santos'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('families.0.satellite', 'House Church')
            ->where('families.0.can_open', false));
        // A Role at both satellites (no church-wide Role, DECISIONS 2026-11-08) opens a family at its own satellite's
        // address only: a visitor at the DXB door is not theirs to open there, but is at the House door.
        $churchWide = staffMember(RoleEnum::Lifeshaper, atSatellite($dxb));
        grantRole($churchWide, RoleEnum::Lifeshaper, atSatellite($house));
        $houseDoor = sessionOf(Schedule::query()->where('satellite_id', $house->id)->sole());
        actingAs($churchWide)->get(door('/checkin?q=Santos'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('families.0.can_open', false));
        actingAs($churchWide)->get("/{$houseDoor->code}/checkin?q=Santos")
            ->assertInertia(fn (AssertableInertia $page) => $page->where('families.0.can_open', true));
    });

    it('shows a child\'s health details only when their row is opened @story#105', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'amara' => $amara] = doorChurch();

        actingAs($lifeshaper)->get(door('/checkin?q=Maria'))
            ->assertInertia(fn (AssertableInertia $page) => $page->missing('health'));

        actingAs($lifeshaper)->get(door("/checkin?q=Maria&health={$amara->id}"), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request()),
            'X-Inertia-Partial-Component' => 'Church/Door/Index',
            'X-Inertia-Partial-Data' => 'health',
        ])->assertOk()->assertJsonPath('props.health.nickname', 'Amara')
            ->assertJsonPath('props.health.allergies', 'Peanuts')
            ->assertJsonPath('props.health.illnesses', 'Asthma');
    });

    it('shows a past Session\'s door closed, read-only — the day after, its date is gone @story#206', function (): void {
        ['lifeshaper' => $lifeshaper] = doorChurch();
        travelTo(CarbonImmutable::parse('2026-11-02 09:00:00', 'Asia/Dubai'));   // a Monday

        actingAs($lifeshaper)->get(door('/checkin'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('session.state', 'closed')->where('session.date_label', 'Sat 31 Oct'));
    });

    it('offers its admin no "Open again" on another day — that would open today\'s Session, not this one (Astra) @story#206', function (): void {
        ['dxb' => $dxb] = doorChurch();
        $admin = staffMember(RoleEnum::Admin, atSatellite($dxb));

        travelTo(CarbonImmutable::parse('2026-11-07 09:00:00', 'Asia/Dubai'));   // the next Saturday: 1PM meets again
        actingAs($admin)->get(door('/checkin'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('session.state', 'closed')->where('can_open', false));

        expect(Session::query()->count())->toBe(1);
    });

    it('is not found for a Session at a satellite the Role does not cover, or another church\'s @story#104 @story#207', function (): void {
        ['house' => $house, 'lifeshaper' => $lifeshaper] = doorChurch();
        $theirs = sessionOf(scheduleAt(Satellite::factory()->for(church(['slug' => 'other']))->create()));
        $houseDoor = sessionOf(Schedule::query()->where('satellite_id', $house->id)->sole());

        foreach ([$houseDoor, $theirs] as $session) {
            actingAs($lifeshaper)->get("/{$session->code}/checkin")->assertNotFound();
            actingAs($lifeshaper)->post("/{$session->code}/check-ins", ticked([]))->assertNotFound();
        }
    });

    it('never sends a photo, a phone number or a health detail with the search @story#104', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper] = doorChurch();

        $json = (string) json_encode(actingAs($lifeshaper)->get(door('/checkin?q=Maria'))->viewData('page'));

        expect(preg_match('/Peanuts|Asthma|0501234567|photo/', $json))->toBe(0);
    });

    it('is not there for a parent or someone signed out @story#104', function (): void {
        doorChurch();
        $parent = User::query()->whereHas('guardians')->firstOrFail();

        actingAs($parent)->get(door('/checkin'))->assertNotFound();
        auth()->logout();
        get(door('/checkin'))->assertRedirect('/login');
    });
});

describe('a name card at the door', function (): void {
    it('ticks the one child a card names exactly — typed by a desk scanner or read by the camera — on their family card @story#128', function (string $query): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'amara' => $amara] = doorChurch();
        $amara->forceFill(['middle_name' => 'Joy'])->save();

        actingAs($lifeshaper)->get(door("/checkin?{$query}"))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('families', 1)
            ->where('families.0.primary_name', 'Maria Cruz')
            ->has('families.0.children', 4)
            ->where('scan', ['code' => 'AMARAJOYCRUZ', 'match' => 'exact', 'child_ids' => [$amara->id], 'note' => null]));
    })->with([
        'a desk scanner types it' => ['q=AMARAJOYCRUZ'],
        'in lower case' => ['q=amarajoycruz'],
        'the camera read it' => ['q=AMARAJOYCRUZ&card=1'],
    ]);

    it('shows both families when two children share the key, ticking nobody, and says to tick the right one @story#128', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'dxb' => $dxb, 'amara' => $amara] = doorChurch();
        $amara->forceFill(['middle_name' => 'Joy'])->save();
        childOf(familyAt($dxb), ['first_name' => 'Amara', 'middle_name' => 'Joy', 'last_name' => 'Cruz', 'level' => 2]);

        actingAs($lifeshaper)->get(door('/checkin?q=AMARAJOYCRUZ'))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('families', 2)
            ->where('scan.match', 'several')
            ->where('scan.child_ids', [])
            ->where('scan.note', 'More than one child matches this card — tick the right one.'));
    });

    it('shows a close match — a middle name on one side only — with nobody ticked and a note to check @story#128', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper] = doorChurch();

        // Amara Cruz has no middle name in the app; her card has one.
        actingAs($lifeshaper)->get(door('/checkin?q=AMARAJOYCRUZ&card=1'))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('families', 1)
            ->where('scan.match', 'close')
            ->where('scan.child_ids', [])
            ->where('scan.note', 'Check this is the right child — the name on the card is not an exact match.'));
    });

    it('says when no child matches a card — a camera read finds nothing else; a typed word is an ordinary search, saying so only when nothing is found @story#129', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper] = doorChurch();

        actingAs($lifeshaper)->get(door('/checkin?q=NOBODYHEREATALL&card=1'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('families', [])
            ->where('scan', ['code' => 'NOBODYHEREATALL', 'match' => 'none', 'child_ids' => [], 'note' => 'No child matches this card: NOBODYHEREATALL']));
        // Typed in capitals, it is a surname: the families, and no card note.
        actingAs($lifeshaper)->get(door('/checkin?q=SANTOS'))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('families', 1)
            ->where('families.0.primary_name', 'Grace Santos')
            ->where('scan', null));
        // Typed, matching no card and no family: then it says so.
        actingAs($lifeshaper)->get(door('/checkin?q=NOBODYHEREATALL'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('families', [])
            ->where('scan.note', 'No child matches this card: NOBODYHEREATALL'));
        actingAs($lifeshaper)->get(door('/checkin?q=Maria Cruz'))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('families', 1)
            ->where('scan', null));
        actingAs($lifeshaper)->get(door('/checkin?q=&card=1'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('scan', null));
    });

    it('matches only this church\'s children by a card @story#129', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper] = doorChurch();
        childOf(familyAt(Satellite::factory()->for(church(['slug' => 'other']))->create()), ['first_name' => 'Zed', 'middle_name' => null, 'last_name' => 'Elsewhere', 'level' => 1]);

        actingAs($lifeshaper)->get(door('/checkin?q=ZEDELSEWHERE&card=1'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('families', [])
            ->where('scan.match', 'none'));
    });
});

describe('checking children in', function (): void {
    it('checks a family\'s children in to today\'s open class, each in this satellite\'s Squad @story#106', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'amara' => $amara, 'ben' => $ben] = doorChurch();

        actingAs($lifeshaper)->post(door('/check-ins'), ticked([[$amara], [$ben]]))
            ->assertRedirect()->assertSessionHas('success', 'Checked in Amara and Ben.')->assertSessionMissing('error');

        $session = Session::query()->sole();
        expect([$session->schedule_id, $session->date->toDateString()])->toBe([$onePm->id, '2026-10-31'])
            ->and(CheckIn::query()->with('squad')->orderBy('id')->get()->map(fn (CheckIn $c): array => [$c->child_id, $c->squad->name(), $c->checked_in_by])->all())
            ->toBe([[$amara->id, 'Level 2 Girls', $lifeshaper->id], [$ben->id, 'Level 1', $lifeshaper->id]]);
    });

    it('checks a visiting child from another satellite in, in THIS satellite\'s Squad @story#106', function (): void {
        ['dxb' => $dxb, 'onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'visitor' => $visitor] = doorChurch();

        actingAs($lifeshaper)->post(door('/check-ins'), ticked([[$visitor]]))->assertSessionHas('success', 'Checked in Bea.');

        $in = CheckIn::query()->with('squad')->sole();
        expect([$in->squad->satellite_id, $in->squad->name()])->toBe([$dxb->id, 'Level 3 Girls']);
    });

    it('says a child already in is already in, and makes no second Check-in @story#106', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'amara' => $amara] = doorChurch();
        checkedIn(onePmSession(), $amara);

        actingAs($lifeshaper)->post(door('/check-ins'), ticked([[$amara]]))->assertSessionHas('success', 'Amara was already checked in.');

        expect(CheckIn::query()->count())->toBe(1);
    });

    it('makes an inactive child active again, noted, and checks them in @story#107', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'noah' => $noah] = doorChurch();

        actingAs($lifeshaper)->post(door('/check-ins'), ticked([[$noah, null, true]]))->assertSessionHas('success', 'Checked in Noah.');

        expect($noah->fresh()?->status)->toBe(ChildStatusEnum::Active)
            ->and(Note::query()->where('noteable_id', $noah->id)->sole()->body)->toBe('Noah: status changed from Inactive to Active.')
            ->and(CheckIn::query()->sole()->child_id)->toBe($noah->id);
    });

    it('saves the Level picked at the door for a child with none, notes it, and checks them in @story#107', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'lola' => $lola] = doorChurch();

        actingAs($lifeshaper)->post(door('/check-ins'), ticked([[$lola, 3]]))->assertSessionHas('success', 'Checked in Lola.');

        $child = $lola->fresh();
        expect([$child?->level, $child?->level_set_by])->toBe([3, $lifeshaper->id])
            ->and(Note::query()->where('noteable_id', $lola->id)->sole()->body)->toBe('Lola: Level changed from no Level to Level 3.')
            ->and(CheckIn::query()->with('squad')->sole()->squad->name())->toBe('Level 3 Girls');
    });

    it('never changes a Level the child already has at the door @story#107', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'amara' => $amara] = doorChurch();

        actingAs($lifeshaper)->post(door('/check-ins'), ticked([[$amara, 4]]))->assertSessionHas('success');

        expect($amara->fresh()?->level)->toBe(2)->and(Note::query()->count())->toBe(0);
    });

    it('refuses a Graduated child, an inactive child not made active, and a child with no Level, saying why @story#108', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'amara' => $amara, 'noah' => $noah, 'lola' => $lola, 'ben' => $ben] = doorChurch();
        $ben->forceFill(['status' => ChildStatusEnum::Graduated])->save();

        actingAs($lifeshaper)->post(door('/check-ins'), ticked([[$amara], [$ben, null, true], [$noah], [$lola]]))
            ->assertSessionHas('success', 'Checked in Amara.')
            ->assertSessionHas('error', 'Not checked in: Ben has Graduated from NxtGen; Noah is inactive — make them active again first; Lola needs a Level first.');

        expect(CheckIn::query()->pluck('child_id')->all())->toBe([$amara->id])
            ->and($noah->fresh()?->status)->toBe(ChildStatusEnum::Inactive)
            ->and($ben->fresh()?->status)->toBe(ChildStatusEnum::Graduated);
    });

    it('checks in only at a Session the Role covers, and this church\'s children @story#109', function (): void {
        ['house' => $house, 'lifeshaper' => $lifeshaper, 'amara' => $amara] = doorChurch();
        $houseDoor = sessionOf(Schedule::query()->where('satellite_id', $house->id)->sole());
        $otherChild = childOf(familyAt(Satellite::factory()->for(church())->create()), ['level' => 1]);

        actingAs($lifeshaper)->post("/{$houseDoor->code}/check-ins", ticked([[$amara]]))->assertNotFound();
        actingAs($lifeshaper)->post(door('/check-ins'), ticked([[$amara], [$otherChild]]))->assertNotFound();

        expect(CheckIn::query()->count())->toBe(0)->and(Session::query()->count())->toBe(2);
    });

    it('needs at least one ticked child, and a Level from the chart @story#109', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'lola' => $lola] = doorChurch();

        actingAs($lifeshaper)->post(door('/check-ins'), ['children' => []])->assertSessionHasErrors('children');
        actingAs($lifeshaper)->post(door('/check-ins'), ticked([[$lola, 9]]))->assertSessionHasErrors('children.0.level');
    });
});

describe('undoing a Check-in', function (): void {
    it('undoes a Check-in, and the child can be checked in again @story#110', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'ben' => $ben] = doorChurch();
        $in = checkedIn(onePmSession(), $ben);

        actingAs($lifeshaper)->put(door("/check-ins/{$in->id}/undo"))->assertRedirect()->assertSessionHas('success', 'Undid Ben\'s check-in.');
        expect([$in->fresh()?->isIn(), $in->fresh()?->undone_by])->toBe([false, $lifeshaper->id]);

        actingAs($lifeshaper)->post(door('/check-ins'), ticked([[$ben]]))->assertSessionHas('success', 'Checked in Ben.');
        expect(CheckIn::query()->sole()->isIn())->toBeTrue();
    });

    it('undoes only a Check-in of the Session at the address, at a satellite the Role covers, in this church @story#111', function (): void {
        ['house' => $house, 'onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'visitor' => $visitor, 'amara' => $amara] = doorChurch();
        $houseDoor = sessionOf(Schedule::query()->where('satellite_id', $house->id)->sole());
        $atHouse = checkedIn($houseDoor, $visitor);
        $lastWeek = checkedIn(sessionOf($onePm, '2026-10-24'), $amara);

        actingAs($lifeshaper)->put(door("/check-ins/{$atHouse->id}/undo"))->assertNotFound();      // another Session's
        actingAs($lifeshaper)->put(door("/check-ins/{$lastWeek->id}/undo"))->assertNotFound();     // last week's
        actingAs($lifeshaper)->put("/{$houseDoor->code}/check-ins/{$atHouse->id}/undo")->assertNotFound();   // not their satellite
        $otherSatellite = satelliteOf(church(['slug' => 'other']), ['slug' => 'other']);
        actingAs(staffMember(RoleEnum::Admin, atSatellite($otherSatellite)))->put("/{$houseDoor->code}/check-ins/{$atHouse->id}/undo")->assertNotFound();

        expect([$atHouse->fresh()?->isIn(), $lastWeek->fresh()?->isIn()])->toBe([true, true]);
    });
});

/* A class admin (TODO #9; plan §3.2 "Check in / undo: events of S"): their own class at the door, no other. */
describe('an open Session (spec item 15)', function (): void {
    it('says whether the class is open or closed, and offers Close and Open again to its admins only @story#202', function (): void {
        ['dxb' => $dxb, 'onePm' => $onePm, 'lifeshaper' => $lifeshaper] = doorChurch();
        $admin = staffMember(RoleEnum::Admin, atSatellite($dxb));

        actingAs($lifeshaper)->get(door('/checkin'))->assertInertia(fn (AssertableInertia $p) => $p->where('session.state', 'open')->where('can_open', false));
        actingAs($admin)->get(door('/checkin'))->assertInertia(fn (AssertableInertia $p) => $p->where('session.state', 'open')->where('can_open', true));
        actingAs(classAdminOf($onePm))->get(door('/checkin'))->assertInertia(fn (AssertableInertia $p) => $p->where('can_open', true));

        onePmSession()->update(['closed_at' => now()]);
        actingAs($admin)->get(door('/checkin'))->assertInertia(fn (AssertableInertia $p) => $p->where('session.state', 'closed')->where('can_open', true));
    });

    it('checks nobody in once the class is closed — saying so @story#109', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'amara' => $amara] = doorChurch();

        onePmSession()->update(['closed_at' => now()]);
        actingAs($lifeshaper)->post(door('/check-ins'), ticked([[$amara]]))
            ->assertSessionHas('error', '1PM is closed.');

        expect(CheckIn::query()->count())->toBe(0);
    });

    it('checks a late child in while the class is still open (Roy: ¾ of the class in) @story#106', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'amara' => $amara] = doorChurch();
        travelTo(CarbonImmutable::parse('2026-10-31 14:45:00', 'Asia/Dubai'));

        actingAs($lifeshaper)->post(door('/check-ins'), ticked([[$amara]]))->assertSessionHas('success');
    });

    it('undoes nothing once the class is closed @story#111', function (): void {
        ['onePm' => $onePm, 'lifeshaper' => $lifeshaper, 'ben' => $ben] = doorChurch();
        $session = onePmSession();
        $in = checkedIn($session, $ben);
        $session->update(['closed_at' => now()]);

        actingAs($lifeshaper)->put(door("/check-ins/{$in->id}/undo"))->assertSessionHas('error', '1PM is closed.');

        expect($in->fresh()?->isIn())->toBeTrue();
    });
});

describe('a class admin at the door', function (): void {
    it('opens their own class\'s door and checks children in there @story#182', function (): void {
        ['onePm' => $onePm, 'amara' => $amara] = doorChurch();
        $classAdmin = classAdminOf($onePm);

        actingAs($classAdmin)->get(door('/checkin'))->assertOk()->assertInertia(fn (AssertableInertia $p) => $p->where('session.name', '1PM'));
        actingAs($classAdmin)->post(door('/check-ins'), ['children' => [['child_id' => $amara->id, 'level' => null, 'make_active' => false]]])
            ->assertSessionHas('success');

        expect(CheckIn::query()->where('child_id', $amara->id)->exists())->toBeTrue();
    });

    it('cannot open or check in at another class of the same satellite @story#183', function (): void {
        ['amara' => $amara, 'onePm' => $onePm] = doorChurch();
        $sevenPm = sessionOf(Schedule::query()->where('name', '7PM')->sole());

        actingAs(classAdminOf($onePm))->get("/{$sevenPm->code}/checkin")->assertNotFound();
        actingAs(classAdminOf($onePm))->post("/{$sevenPm->code}/check-ins", ['children' => [['child_id' => $amara->id, 'level' => null, 'make_active' => false]]])
            ->assertNotFound();

        expect(CheckIn::query()->count())->toBe(0);
    });
});
