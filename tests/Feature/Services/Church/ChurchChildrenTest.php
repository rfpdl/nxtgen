<?php

declare(strict_types=1);

use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Data\Models\Note;
use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Enums\ChildStatusEnum;
use App\Enums\GenderEnum;
use App\Enums\RoleEnum;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Tests\Support\atSatellite;
use function Tests\Support\atTheSameTime;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\grantRole;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;
use function Tests\Support\staffMember;
use function Tests\Support\withCommittedWorld;

/*
 * The children list (plan §6 R1 "families/children lists") and correcting a child's Level (plan §3.2, DECISIONS
 * 2026-10-17 — it replaced "set a child's group"; since DECISIONS 2026-11-08 an admin of the child's satellite). The child's age suggests a Level at
 * registration; the child stores it and otherwise keeps it until Moving-Up — this is the admin's correction.
 */

/** @return array{church: Church, dxb: Satellite, house: Satellite, amara: Child, bea: Child} */
function kidsChurch(): array
{
    $church = church(['slug' => 'ccf']);
    $dxb = satelliteOf($church, ['name' => 'CCF DXB', 'slug' => 'dxb']);
    $house = satelliteOf($church, ['name' => 'House Church', 'slug' => 'house']);
    $a = familyAt($dxb);
    $b = familyAt($house);
    $amara = childOf($a, ['first_name' => 'Alexis', 'nickname' => 'Amara', 'gender' => GenderEnum::Girl, 'allergies' => 'Peanuts', 'level' => 2, 'date_of_birth' => now()->subYears(7)->toDateString()]);
    $bea = childOf($b, ['first_name' => 'Beatrice', 'nickname' => 'Bea', 'gender' => GenderEnum::Girl, 'allergies' => null, 'status' => ChildStatusEnum::Inactive]);

    return compact('church', 'dxb', 'house', 'amara', 'bea');
}

describe('the children list', function (): void {
    // DECISIONS 2026-11-08: every page shows only the satellite in the address — an admin at both satellites sees each
    // satellite's children at that satellite's address. No church-wide Role (spec item 9).
    it('lists every child an admin of every satellite can see, with Level, age, status and an allergy flag @story#58', function (): void {
        ['dxb' => $dxb, 'house' => $house, 'amara' => $amara] = kidsChurch();
        $admin = staffMember(RoleEnum::Admin, atSatellite($dxb));
        grantRole($admin, RoleEnum::Admin, atSatellite($house));

        actingAs($admin)->get('/house/admin/children')
            ->assertOk()->assertInertia(fn (AssertableInertia $p) => $p->has('children', 1)->where('children.0.nickname', 'Bea'));
        actingAs($admin)->get('/dxb/admin/children')
            ->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
            ->component('Church/Children/Index')
            ->has('children', 1)
            ->where('children.0.nickname', 'Amara')
            ->where('children.0.level', ['number' => 2, 'name' => 'Level 2'])
            ->where('children.0.age', 7)
            ->where('children.0.status_label', 'Active')
            ->where('children.0.has_allergies', true)
            ->where('children.0.satellite', 'CCF DXB')
            ->where('children.0.family_id', $amara->family_id)
            ->has('options.levels', 5)->where('options.levels.4', ['level' => 0, 'name' => 'No Level'])->missing('options.satellites')->has('enums.child_status', 3));
    });

    it('shows a satellite admin only their satellite @story#58', function (): void {
        ['church' => $church, 'house' => $house] = kidsChurch();

        actingAs(staffMember(RoleEnum::Admin, atSatellite($house)))->get('/house/admin/children')
            ->assertInertia(fn (AssertableInertia $p) => $p->has('children', 1)->where('children.0.nickname', 'Bea'));
    });

    it('searches and filters by Level (or No Level) and status — never widening the viewer\'s scope @story#58', function (): void {
        ['church' => $church, 'dxb' => $dxb, 'house' => $house] = kidsChurch();
        $admin = staffMember(RoleEnum::Admin, atSatellite($dxb));
        grantRole($admin, RoleEnum::Admin, atSatellite($house));

        childOf(familyAt($house), ['first_name' => 'Amara', 'nickname' => 'Amara H', 'level' => 2]);

        actingAs($admin)->get('/house/admin/children?q=bea')->assertInertia(fn (AssertableInertia $p) => $p->has('children', 1)->where('children.0.nickname', 'Bea')->where('filters.q', 'bea'));
        actingAs($admin)->get('/dxb/admin/children?q=bea')->assertInertia(fn (AssertableInertia $p) => $p->has('children', 0));
        actingAs($admin)->get('/dxb/admin/children?level=2')->assertInertia(fn (AssertableInertia $p) => $p->has('children', 1)->where('children.0.nickname', 'Amara')->where('filters.level', 2));
        actingAs($admin)->get('/house/admin/children?level=0')->assertInertia(fn (AssertableInertia $p) => $p->has('children', 1)->where('children.0.nickname', 'Bea')->where('filters.level', 0));
        actingAs($admin)->get('/house/admin/children?status=inactive')->assertInertia(fn (AssertableInertia $p) => $p->has('children', 1)->where('children.0.nickname', 'Bea'));

        // DECISIONS 2026-11-08: the satellite filter is gone — the address satellite is the only one. A `?satellite=`
        // naming another is not read: the list stays the address's, for an admin at both as for an admin at one.
        actingAs($admin)->get("/house/admin/children?satellite={$dxb->id}")->assertInertia(fn (AssertableInertia $p) => $p->has('children', 2)->missing('filters.satellite'));
        actingAs($admin)->get("/dxb/admin/children?satellite={$house->id}")
            ->assertInertia(fn (AssertableInertia $p) => $p->has('children', 1)->where('children.0.nickname', 'Amara'));
        actingAs(staffMember(RoleEnum::Admin, atSatellite($dxb)))->get("/dxb/admin/children?satellite={$house->id}")
            ->assertInertia(fn (AssertableInertia $p) => $p->has('children', 1)->where('children.0.nickname', 'Amara'));
    });

    // Found in review 4.
    it('finds children by their family\'s parent name, email or phone too — as the search box says @story#58', function (): void {
        ['dxb' => $dxb, 'amara' => $amara] = kidsChurch();
        guardianOf($amara->family()->firstOrFail(), User::factory()->create(['email' => 'janice@example.com']), ['first_name' => 'Janice', 'last_name' => 'Ramos', 'phone' => '0544897346']);
        $admin = staffMember(RoleEnum::Admin, atSatellite($dxb));

        foreach (['Janice', 'janice@exam', '4489734'] as $q) {
            actingAs($admin)->get('/dxb/admin/children?q='.urlencode($q))->assertInertia(fn (AssertableInertia $p) => $p
                ->has('children', 1)->where('children.0.nickname', 'Amara'));
        }
        // A child's own name finds that child, not their brothers and sisters.
        childOf($amara->family()->firstOrFail(), ['first_name' => 'Ben', 'nickname' => 'Ben', 'gender' => GenderEnum::Boy]);
        actingAs($admin)->get('/dxb/admin/children?q=Amara')->assertInertia(fn (AssertableInertia $p) => $p->has('children', 1)->where('children.0.nickname', 'Amara'));
    });

    it('is not there for someone with no role in the church @story#59', function (): void {
        kidsChurch();

        actingAs(User::factory()->create())->get('/dxb/admin/children')->assertNotFound();
    });
});

describe('correcting a child\'s Level', function (): void {
    // DECISIONS 2026-11-08 (spec item 9): a church admin's correction is now the admin of the child's satellite.
    it('lets an admin of the child\'s satellite move a child, records who and when, and notes it @story#64', function (): void {
        ['house' => $house, 'bea' => $bea] = kidsChurch();
        $admin = staffMember(RoleEnum::Admin, atSatellite($house), ['name' => 'Ana Admin']);

        actingAs($admin)->put("/house/admin/children/{$bea->id}/level", ['level' => 1])->assertRedirect()->assertSessionHas('success', 'Bea is now in Level 1.');

        $bea->refresh();
        expect($bea->level)->toBe(1)
            ->and($bea->level_set_by)->toBe($admin->id)
            ->and($bea->level_set_at)->not->toBeNull()
            ->and(Note::query()->where('noteable_type', 'child')->where('noteable_id', $bea->id)->sole()->body)
            ->toBe('Bea: Level changed from no Level to Level 1.');
    });

    it('notes the Level the child moved from @story#64', function (): void {
        ['dxb' => $dxb, 'amara' => $amara] = kidsChurch();

        actingAs(staffMember(RoleEnum::Admin, atSatellite($dxb)))->put("/dxb/admin/children/{$amara->id}/level", ['level' => 3])->assertSessionHas('success');

        expect($amara->fresh()?->level)->toBe(3)
            ->and(Note::query()->sole()->body)->toBe('Amara: Level changed from Level 2 to Level 3.');
    });

    it('refuses a Level that is not on the chart @story#65', function (int $level): void {
        ['dxb' => $dxb, 'amara' => $amara] = kidsChurch();

        actingAs(staffMember(RoleEnum::Admin, atSatellite($dxb)))
            ->put("/dxb/admin/children/{$amara->id}/level", ['level' => $level])
            ->assertSessionHasErrors(['level' => 'Please choose one of the church\'s Levels.']);

        expect($amara->fresh()?->level)->toBe(2)->and(Note::query()->count())->toBe(0);
    })->with([0, 5, 99]);

    it('refuses a missing Level @story#65', function (): void {
        ['dxb' => $dxb, 'amara' => $amara] = kidsChurch();

        actingAs(staffMember(RoleEnum::Admin, atSatellite($dxb)))->put("/dxb/admin/children/{$amara->id}/level", [])
            ->assertSessionHasErrors(['level' => 'Please choose one of the church\'s Levels.']);

        expect($amara->fresh()?->level)->toBe(2);
    });

    it('has no gender rule — a girl at Level 2 moves to Level 3 like anyone (her Squad follows from her gender)', function (): void {
        ['dxb' => $dxb, 'amara' => $amara] = kidsChurch();

        actingAs(staffMember(RoleEnum::Admin, atSatellite($dxb)))->put("/dxb/admin/children/{$amara->id}/level", ['level' => 3])->assertSessionHasNoErrors();
    });

    it('does not reach a child of another church @story#65', function (): void {
        ['dxb' => $dxb] = kidsChurch();
        $foreign = childOf(familyAt(satelliteOf(church(['slug' => 'other']))), ['level' => 1]);

        actingAs(staffMember(RoleEnum::Admin, atSatellite($dxb)))->put("/dxb/admin/children/{$foreign->id}/level", ['level' => 2])->assertNotFound();

        expect($foreign->fresh()?->level)->toBe(1);
    });

    // DECISIONS 2026-11-08 (spec item 9): no longer church admins only — the child's satellite's admin may (above);
    // a Lifeshaper there, or an admin of another satellite, may not.
    it('is for an admin of the child\'s satellite only — not a Lifeshaper there @story#65', function (): void {
        ['dxb' => $dxb, 'amara' => $amara] = kidsChurch();

        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)))
            ->put("/dxb/admin/children/{$amara->id}/level", ['level' => 1])->assertNotFound();

        expect($amara->fresh()?->level)->toBe(2);
    });

    it('is not for an admin of another satellite — at their own address or at the child\'s @story#65', function (): void {
        ['house' => $house, 'amara' => $amara] = kidsChurch();
        $houseAdmin = staffMember(RoleEnum::Admin, atSatellite($house));

        actingAs($houseAdmin)->put("/house/admin/children/{$amara->id}/level", ['level' => 1])->assertNotFound();
        actingAs($houseAdmin)->put("/dxb/admin/children/{$amara->id}/level", ['level' => 1])->assertNotFound();

        expect($amara->fresh()?->level)->toBe(2)->and(Note::query()->count())->toBe(0);
    });

    it('tells the family page who may change Levels, and offers the church\'s Levels', function (): void {
        ['church' => $church, 'dxb' => $dxb, 'amara' => $amara] = kidsChurch();

        actingAs(staffMember(RoleEnum::Admin, atSatellite($dxb)))->get("/dxb/admin/families/{$amara->family_id}")
            ->assertInertia(fn (AssertableInertia $p) => $p->where('can_set_level', true)->has('levels', 4)
                ->where('levels.1', ['level' => 2, 'name' => 'Level 2'])
                ->where('family.children.0.level', ['number' => 2, 'name' => 'Level 2']));
        actingAs(staffMember(RoleEnum::Admin, atSatellite($dxb)))->get("/dxb/serve/families/{$amara->family_id}")
            ->assertInertia(fn (AssertableInertia $p) => $p->where('can_set_level', false)->where('levels', []));
    });
});

// Roy's decision, 2026-10-16.
it('shows the children list 50 at a time, filters and all', function (): void {
    ['church' => $church, 'dxb' => $dxb] = kidsChurch();
    $family = familyAt($dxb);
    Child::factory()->count(58)->for($family)->create(['church_id' => $family->church_id, 'status' => ChildStatusEnum::Active]);
    $admin = staffMember(RoleEnum::Admin, atSatellite($dxb));

    actingAs($admin)->get('/dxb/admin/children?status=active')->assertInertia(fn (AssertableInertia $p) => $p
        ->has('children', 50)->where('pagination.total', 59)->where('pagination.page', 1)->where('pagination.last_page', 2));
    actingAs($admin)->get('/dxb/admin/children?status=active&page=2')->assertInertia(fn (AssertableInertia $p) => $p
        ->has('children', 9)->where('pagination.page', 2));
});

// Astra 2026-11-09: the family moving to another satellite while a correction waits for the church's lock — the old
// satellite's admin, already let in, is refused once the move commits: nothing changes, nothing is noted.
it('refuses a Level or status fix for a child whose family moves to another satellite meanwhile — two connections @story#65 @story#63', function (string $what): void {
    $church = church(['slug' => 'ccf']);
    $dxb = satelliteOf($church, ['slug' => 'dxb']);
    $house = satelliteOf($church, ['slug' => 'house']);
    $family = familyAt($dxb);
    $amara = childOf($family, ['level' => 2]);
    $admin = staffMember(RoleEnum::Admin, atSatellite($dxb));

    withCommittedWorld(function () use ($church, $family, $house, $amara, $admin, $what): void {
        [, $status] = atTheSameTime(
            function () use ($church, $family, $house): int {
                DB::transaction(function () use ($church, $family, $house): void {
                    Church::query()->whereKey($church->id)->lockForUpdate()->sole();
                    $family->newQuery()->whereKey($family->id)->update(['home_satellite_id' => $house->id]);
                    usleep(1_000_000);
                });

                return 0;
            },
            function () use ($amara, $admin, $what): int {
                usleep(150_000);

                return $what === 'level'
                    ? actingAs($admin)->put("/dxb/admin/children/{$amara->id}/level", ['level' => 3])->status()
                    : actingAs($admin)->put("/dxb/admin/children/{$amara->id}/status", ['status' => 'inactive'])->status();
            },
        );

        expect($status)->toBe(404)
            ->and([$amara->fresh()?->level, $amara->fresh()?->status])->toBe([2, ChildStatusEnum::Active])
            ->and(Note::query()->count())->toBe(0);
    });
})->with(['level', 'status']);
