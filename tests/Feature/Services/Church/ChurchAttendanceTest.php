<?php

declare(strict_types=1);

use App\Data\Models\Church;
use App\Data\Models\Family;
use App\Data\Models\Satellite;
use App\Data\Models\Schedule;
use App\Data\Models\Session;
use App\Data\Models\User;
use App\Enums\GenderEnum;
use App\Enums\RoleEnum;
use App\Enums\WeekdayEnum;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\travelTo;
use function Tests\Support\atChurch;
use function Tests\Support\atSatellite;
use function Tests\Support\checkedIn;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\exportedRows;
use function Tests\Support\familyAt;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;
use function Tests\Support\staffMember;

/*
 * Attendance (DECISIONS 2026-10-26: reports are admins only, with Excel): an admin picks a satellite, a class and a
 * date and sees the children checked in to that Session, by Squad, with a count per Squad and in total — and
 * downloads the same rows as Excel. A church admin any satellite, a satellite admin their own; a Lifeshaper has no
 * Attendance. AccessScope::canSeeAttendance owns who may.
 *
 * It is Saturday 31 Oct 2026, 15:00 in Dubai, throughout.
 */

beforeEach(function (): void {
    travelTo(CarbonImmutable::parse('2026-10-31 15:00:00', 'Asia/Dubai'));
});

/** @return array{church: Church, dxb: Satellite, house: Satellite, onePm: Schedule, sevenPm: Schedule, houseClass: Schedule, admin: User, lifeshaper: User} */
function attendanceChurch(): array
{
    $church = church(['slug' => 'ccf', 'name' => 'CCF', 'timezone' => 'Asia/Dubai']);
    $dxb = satelliteOf($church, ['name' => 'CCF DXB']);
    $house = satelliteOf($church, ['name' => 'House Church']);
    $onePm = scheduleAt($dxb, ['name' => '1PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '13:00', 'ends_at' => '15:00']);
    $sevenPm = scheduleAt($dxb, ['name' => '7PM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '19:00', 'ends_at' => '21:00']);
    $houseClass = scheduleAt($house, ['name' => '10AM', 'weekday' => WeekdayEnum::Saturday, 'starts_at' => '10:00']);

    $cruz = familyAt($dxb);
    guardianOf($cruz, User::factory()->create(), ['first_name' => 'Maria', 'last_name' => 'Cruz']);
    $lifeshaper = staffMember(RoleEnum::Lifeshaper, atSatellite($dxb), ['name' => 'Liza Reyes']);
    $session = sessionOf($onePm);
    $by = ['checked_in_by' => $lifeshaper->id];
    checkedIn($session, childOf($cruz, ['nickname' => 'Zoe', 'first_name' => 'Zoe', 'middle_name' => null, 'last_name' => 'Cruz', 'level' => 2, 'gender' => GenderEnum::Girl]), $by + ['checked_in_at' => '2026-10-31 09:02:00']);
    checkedIn($session, childOf($cruz, ['nickname' => 'Amara', 'first_name' => 'Amara', 'middle_name' => 'Joy', 'last_name' => 'Cruz', 'level' => 2, 'gender' => GenderEnum::Girl]), $by + ['checked_in_at' => '2026-10-31 09:05:00']);
    checkedIn($session, childOf($cruz, ['nickname' => 'Ben', 'first_name' => 'Benjamin', 'middle_name' => null, 'last_name' => 'Cruz', 'level' => 1, 'gender' => GenderEnum::Boy]), $by + ['checked_in_at' => '2026-10-31 08:58:00']);
    checkedIn($session, childOf($cruz, ['nickname' => 'Noah', 'level' => 3, 'gender' => GenderEnum::Boy]), $by + ['undone_at' => now()]);
    checkedIn(sessionOf($sevenPm, '2026-10-24'), childOf($cruz, ['nickname' => 'Lola', 'level' => 1]));

    return [
        'church' => $church, 'dxb' => $dxb, 'house' => $house, 'onePm' => $onePm, 'sevenPm' => $sevenPm, 'houseClass' => $houseClass,
        'admin' => staffMember(RoleEnum::Admin, atChurch($church)), 'lifeshaper' => $lifeshaper,
    ];
}

describe('the Attendance page', function (): void {
    it('opens on today\'s class with a Session: the children in by Squad, each with name, Level, time and who checked them in, a count per Squad and a total @story#112', function (): void {
        ['dxb' => $dxb, 'onePm' => $onePm, 'admin' => $admin] = attendanceChurch();

        actingAs($admin)->get('/ccf/attendance')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Church/Attendance/Index')
            ->where('satellite_id', $dxb->id)
            ->where('class_id', $onePm->id)
            ->where('date', '2026-10-31')
            ->where('date_label', 'Sat 31 Oct')
            ->where('satellites.0.name', 'CCF DXB')
            ->where('satellites.0.classes.0', ['id' => $onePm->id, 'name' => '1PM', 'retired' => false])
            ->where('satellites.1.name', 'House Church')
            ->where('total', 3)
            ->has('squads', 2)
            ->where('squads.0.name', 'Level 1')
            ->where('squads.0.count', 1)
            ->where('squads.0.children.0.nickname', 'Ben')
            ->where('squads.0.children.0.full_name', 'Benjamin Cruz')
            ->where('squads.0.children.0.checked_in_at', '12:58')
            ->where('squads.0.children.0.checked_in_by', 'Liza Reyes')
            ->where('squads.1.name', 'Level 2 Girls')
            ->where('squads.1.count', 2)
            ->where('squads.1.children.0.nickname', 'Amara')
            ->where('squads.1.children.0.full_name', 'Amara Joy Cruz')
            ->where('squads.1.children.0.checked_in_at', '13:05')
            ->where('squads.1.children.1.nickname', 'Zoe'));
    });

    it('opens on the latest Session at the first satellite when none is today @story#112', function (): void {
        ['onePm' => $onePm, 'admin' => $admin] = attendanceChurch();
        travelTo(CarbonImmutable::parse('2026-11-05 10:00:00', 'Asia/Dubai'));

        actingAs($admin)->get('/ccf/attendance')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('class_id', $onePm->id)->where('date', '2026-10-31')->where('total', 3));
    });

    it('shows the picked satellite, class and date — and says when no one was checked in @story#112', function (): void {
        ['dxb' => $dxb, 'sevenPm' => $sevenPm, 'admin' => $admin] = attendanceChurch();

        actingAs($admin)->get("/ccf/attendance?satellite={$dxb->id}&class={$sevenPm->id}&date=2026-10-24")->assertInertia(fn (AssertableInertia $page) => $page
            ->where('class_id', $sevenPm->id)->where('date', '2026-10-24')->where('total', 1)->where('squads.0.children.0.nickname', 'Lola'));

        actingAs($admin)->get("/ccf/attendance?satellite={$dxb->id}&class={$sevenPm->id}&date=2026-10-31")->assertInertia(fn (AssertableInertia $page) => $page
            ->where('total', 0)->where('squads', []));
    });

    it('gives a satellite admin only their own satellite, and opens on it @story#112', function (): void {
        ['house' => $house, 'houseClass' => $houseClass] = attendanceChurch();

        actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))->get('/ccf/attendance')->assertInertia(fn (AssertableInertia $page) => $page
            ->has('satellites', 1)->where('satellites.0.name', 'House Church')
            ->where('satellite_id', $house->id)->where('class_id', $houseClass->id)->where('date', '2026-10-31')->where('total', 0));
    });

    it('keeps a retired class that met in the picker, marked retired, and shows its Sessions; a retired class that never met stays out @story#112 @story#113', function (): void {
        ['dxb' => $dxb, 'onePm' => $onePm, 'admin' => $admin] = attendanceChurch();
        $onePm->forceFill(['is_active' => false])->save();
        $never = scheduleAt($dxb, ['name' => 'Never met', 'is_active' => false]);

        actingAs($admin)->get("/ccf/attendance?class={$onePm->id}&date=2026-10-31")->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('class_id', $onePm->id)
            ->where('total', 3)
            ->has('satellites.0.classes', 2)
            ->where('satellites.0.classes.0.name', '7PM')
            ->where('satellites.0.classes.0.retired', false)
            ->where('satellites.0.classes.1', ['id' => $onePm->id, 'name' => '1PM', 'retired' => true]));
        actingAs($admin)->get("/ccf/attendance?class={$never->id}")->assertNotFound();
    });

    it('asks the database the same number of times however many children are in @story#112', function (): void {
        ['onePm' => $onePm, 'admin' => $admin] = attendanceChurch();
        $count = function () use ($admin, $onePm): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            actingAs($admin)->get("/ccf/attendance?class={$onePm->id}&date=2026-10-31")->assertOk();
            DB::disableQueryLog();

            return count(DB::getQueryLog());
        };
        $few = $count();

        $family = Family::query()->firstOrFail();
        $session = Session::query()->where('schedule_id', $onePm->id)->sole();
        foreach (['Ana', 'Bo', 'Cy', 'Di', 'Ed', 'Flo'] as $i => $nickname) {
            checkedIn($session, childOf($family, ['nickname' => $nickname, 'level' => 1 + $i % 4, 'gender' => $i % 2 === 0 ? GenderEnum::Boy : GenderEnum::Girl]), ['checked_in_by' => User::factory()->create()->id]);
        }

        expect($count())->toBe($few);
    });

    it('refuses another satellite\'s class or satellite to a satellite admin, another church\'s class, a class not at the picked satellite, and a date that is not one @story#113', function (): void {
        ['dxb' => $dxb, 'house' => $house, 'onePm' => $onePm, 'houseClass' => $houseClass, 'admin' => $admin] = attendanceChurch();
        $houseAdmin = staffMember(RoleEnum::Admin, atSatellite($house));
        $theirs = scheduleAt(satelliteOf(church(['slug' => 'other'])));

        actingAs($houseAdmin)->get("/ccf/attendance?class={$onePm->id}")->assertNotFound();
        actingAs($houseAdmin)->get("/ccf/attendance?satellite={$dxb->id}")->assertNotFound();
        actingAs($admin)->get("/ccf/attendance?class={$theirs->id}")->assertNotFound();
        actingAs($admin)->get("/ccf/attendance?satellite={$dxb->id}&class={$houseClass->id}")->assertNotFound();
        actingAs($admin)->get("/ccf/attendance?class={$onePm->id}&date=31-10-2026")->assertNotFound();
    });

    it('is not there for a Lifeshaper, a parent or someone signed out — and a Lifeshaper\'s menu has no Attendance @story#113', function (): void {
        ['lifeshaper' => $lifeshaper, 'admin' => $admin] = attendanceChurch();
        $parent = User::query()->whereHas('guardians')->firstOrFail();

        actingAs($lifeshaper)->get('/ccf/attendance')->assertNotFound();
        actingAs($lifeshaper)->get('/ccf/door')->assertInertia(fn (AssertableInertia $page) => $page->where('church_nav.can_see_attendance', false));
        actingAs($admin)->get('/ccf/door')->assertInertia(fn (AssertableInertia $page) => $page->where('church_nav.can_see_attendance', true));
        actingAs($parent)->get('/ccf/attendance')->assertNotFound();
        auth()->logout();
        get('/ccf/attendance')->assertRedirect('/login');
    });
});

describe('the Attendance Excel', function (): void {
    it('downloads the same rows as the page, named for the satellite, class and date @story#114', function (): void {
        ['dxb' => $dxb, 'onePm' => $onePm, 'admin' => $admin] = attendanceChurch();

        $response = actingAs($admin)->get("/ccf/attendance/export?satellite={$dxb->id}&class={$onePm->id}&date=2026-10-31")->assertOk();

        expect($response->headers->get('Content-Disposition'))->toContain('nxtgen-attendance-ccf-dxb-1pm-2026-10-31.xlsx')
            ->and(exportedRows($response))->toBe([
                'Squad|Child nickname|Child full name|Checked in at|Checked in by',
                'Level 1|Ben|Benjamin Cruz|12:58|Liza Reyes',
                'Level 2 Girls|Amara|Amara Joy Cruz|13:05|Liza Reyes',
                'Level 2 Girls|Zoe|Zoe Cruz|13:02|Liza Reyes',
            ]);
    });

    it('downloads only the header when no one was checked in @story#114', function (): void {
        ['sevenPm' => $sevenPm, 'admin' => $admin] = attendanceChurch();

        expect(exportedRows(actingAs($admin)->get("/ccf/attendance/export?class={$sevenPm->id}&date=2026-10-31")))
            ->toBe(['Squad|Child nickname|Child full name|Checked in at|Checked in by']);
    });

    it('refuses another satellite\'s class to a satellite admin, another church\'s class, and a Lifeshaper @story#115', function (): void {
        ['house' => $house, 'onePm' => $onePm, 'admin' => $admin, 'lifeshaper' => $lifeshaper] = attendanceChurch();
        $theirs = scheduleAt(satelliteOf(church(['slug' => 'other'])));

        actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))->get("/ccf/attendance/export?class={$onePm->id}&date=2026-10-31")->assertNotFound();
        actingAs($admin)->get("/ccf/attendance/export?class={$theirs->id}&date=2026-10-31")->assertNotFound();
        actingAs($lifeshaper)->get("/ccf/attendance/export?class={$onePm->id}&date=2026-10-31")->assertNotFound();
    });
});
