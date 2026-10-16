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
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Tests\Support\atChurch;
use function Tests\Support\atSatellite;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\guardianOf;
use function Tests\Support\satelliteOf;
use function Tests\Support\staffMember;

/*
 * The children list (plan §6 R1 "families/children lists") and correcting a child's Level (plan §3.2, DECISIONS
 * 2026-10-17 — it replaced "set a child's group": church admin only). The child's age suggests a Level at
 * registration; the child stores it and otherwise keeps it until Moving-Up — this is the admin's correction.
 */

/** @return array{church: Church, dxb: Satellite, house: Satellite, amara: Child, bea: Child} */
function kidsChurch(): array
{
    $church = church(['slug' => 'ccf']);
    $dxb = satelliteOf($church, ['name' => 'CCF DXB']);
    $house = satelliteOf($church, ['name' => 'House Church']);
    $a = familyAt($dxb);
    $b = familyAt($house);
    $amara = childOf($a, ['first_name' => 'Alexis', 'nickname' => 'Amara', 'gender' => GenderEnum::Girl, 'allergies' => 'Peanuts', 'level' => 2, 'date_of_birth' => now()->subYears(7)->toDateString()]);
    $bea = childOf($b, ['first_name' => 'Beatrice', 'nickname' => 'Bea', 'gender' => GenderEnum::Girl, 'allergies' => null, 'status' => ChildStatusEnum::Inactive]);

    return compact('church', 'dxb', 'house', 'amara', 'bea');
}

describe('the children list', function (): void {
    it('lists every child a church admin can see, with Level, age, status and an allergy flag', function (): void {
        ['church' => $church, 'amara' => $amara] = kidsChurch();

        actingAs(staffMember(RoleEnum::Admin, atChurch($church)))->get('/ccf/children')
            ->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
            ->component('Church/Children/Index')
            ->has('children', 2)
            ->where('children.0.nickname', 'Amara')
            ->where('children.0.level', 'Level 2')
            ->where('children.0.age', 7)
            ->where('children.0.status', 'Active')
            ->where('children.0.hasAllergies', true)
            ->where('children.0.satellite', 'CCF DXB')
            ->where('children.0.familyId', $amara->family_id)
            ->has('options.levels', 4)->has('options.satellites', 2)->has('options.statuses', 3));
    });

    it('shows a Lifeshaper only their satellite', function (): void {
        ['church' => $church, 'house' => $house] = kidsChurch();

        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($house)))->get('/ccf/children')
            ->assertInertia(fn (AssertableInertia $p) => $p->has('children', 1)->where('children.0.nickname', 'Bea'));
    });

    it('searches and filters by Level, status and satellite — never widening the viewer\'s scope', function (): void {
        ['church' => $church, 'dxb' => $dxb, 'house' => $house] = kidsChurch();
        $admin = staffMember(RoleEnum::Admin, atChurch($church));

        actingAs($admin)->get('/ccf/children?q=bea')->assertInertia(fn (AssertableInertia $p) => $p->has('children', 1)->where('children.0.nickname', 'Bea')->where('filters.q', 'bea'));
        actingAs($admin)->get('/ccf/children?level=2')->assertInertia(fn (AssertableInertia $p) => $p->has('children', 1)->where('children.0.nickname', 'Amara')->where('filters.level', 2));
        actingAs($admin)->get('/ccf/children?status=inactive')->assertInertia(fn (AssertableInertia $p) => $p->has('children', 1)->where('children.0.nickname', 'Bea'));
        actingAs($admin)->get("/ccf/children?satellite={$house->id}")->assertInertia(fn (AssertableInertia $p) => $p->has('children', 1));

        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)))->get("/ccf/children?satellite={$house->id}")
            ->assertInertia(fn (AssertableInertia $p) => $p->has('children', 0));
    });

    it('finds children by their family\'s parent name, email or phone too — as the search box says (review 4)', function (): void {
        ['church' => $church, 'amara' => $amara] = kidsChurch();
        guardianOf($amara->family()->firstOrFail(), User::factory()->create(['email' => 'janice@example.com']), ['first_name' => 'Janice', 'last_name' => 'Ramos', 'phone' => '0544897346']);
        $admin = staffMember(RoleEnum::Admin, atChurch($church));

        foreach (['Janice', 'janice@exam', '4489734'] as $q) {
            actingAs($admin)->get('/ccf/children?q='.urlencode($q))->assertInertia(fn (AssertableInertia $p) => $p
                ->has('children', 1)->where('children.0.nickname', 'Amara'));
        }
        // A child's own name finds that child, not their brothers and sisters.
        childOf($amara->family()->firstOrFail(), ['first_name' => 'Ben', 'nickname' => 'Ben', 'gender' => GenderEnum::Boy]);
        actingAs($admin)->get('/ccf/children?q=Amara')->assertInertia(fn (AssertableInertia $p) => $p->has('children', 1)->where('children.0.nickname', 'Amara'));
    });

    it('is not there for someone with no role in the church', function (): void {
        kidsChurch();

        actingAs(User::factory()->create())->get('/ccf/children')->assertNotFound();
    });
});

describe('correcting a child\'s Level', function (): void {
    it('lets a church admin move a child, records who and when, and notes it', function (): void {
        ['church' => $church, 'bea' => $bea] = kidsChurch();
        $admin = staffMember(RoleEnum::Admin, atChurch($church), ['name' => 'Ana Admin']);

        actingAs($admin)->put("/ccf/children/{$bea->id}/level", ['level' => 1])->assertRedirect()->assertSessionHas('success', 'Bea is now in Level 1.');

        $bea->refresh();
        expect($bea->level)->toBe(1)
            ->and($bea->level_set_by)->toBe($admin->id)
            ->and($bea->level_set_at)->not->toBeNull()
            ->and(Note::query()->where('noteable_type', 'child')->where('noteable_id', $bea->id)->sole()->body)
            ->toBe('Bea: Level changed from no Level to Level 1.');
    });

    it('notes the Level the child moved from', function (): void {
        ['church' => $church, 'amara' => $amara] = kidsChurch();

        actingAs(staffMember(RoleEnum::Admin, atChurch($church)))->put("/ccf/children/{$amara->id}/level", ['level' => 3])->assertSessionHas('success');

        expect($amara->fresh()?->level)->toBe(3)
            ->and(Note::query()->sole()->body)->toBe('Amara: Level changed from Level 2 to Level 3.');
    });

    it('refuses a Level that is not on the chart', function (int $level): void {
        ['church' => $church, 'amara' => $amara] = kidsChurch();

        actingAs(staffMember(RoleEnum::Admin, atChurch($church)))
            ->put("/ccf/children/{$amara->id}/level", ['level' => $level])
            ->assertSessionHasErrors(['level' => 'Please choose one of the church\'s Levels.']);

        expect($amara->fresh()?->level)->toBe(2)->and(Note::query()->count())->toBe(0);
    })->with([0, 5, 99]);

    it('refuses a missing Level', function (): void {
        ['church' => $church, 'amara' => $amara] = kidsChurch();

        actingAs(staffMember(RoleEnum::Admin, atChurch($church)))->put("/ccf/children/{$amara->id}/level", [])
            ->assertSessionHasErrors(['level' => 'Please choose one of the church\'s Levels.']);

        expect($amara->fresh()?->level)->toBe(2);
    });

    it('has no gender rule — a girl at Level 2 moves to Level 3 like anyone (her Squad follows from her gender)', function (): void {
        ['church' => $church, 'amara' => $amara] = kidsChurch();

        actingAs(staffMember(RoleEnum::Admin, atChurch($church)))->put("/ccf/children/{$amara->id}/level", ['level' => 3])->assertSessionHasNoErrors();
    });

    it('does not reach a child of another church', function (): void {
        ['church' => $church] = kidsChurch();
        $foreign = childOf(familyAt(satelliteOf(church(['slug' => 'other']))), ['level' => 1]);

        actingAs(staffMember(RoleEnum::Admin, atChurch($church)))->put("/ccf/children/{$foreign->id}/level", ['level' => 2])->assertNotFound();

        expect($foreign->fresh()?->level)->toBe(1);
    });

    it('is for church admins only — not a satellite admin, not a Lifeshaper', function (RoleEnum $role): void {
        ['dxb' => $dxb, 'amara' => $amara] = kidsChurch();

        actingAs(staffMember($role, atSatellite($dxb)))
            ->put("/ccf/children/{$amara->id}/level", ['level' => 1])->assertNotFound();

        expect($amara->fresh()?->level)->toBe(2);
    })->with([RoleEnum::Admin, RoleEnum::Lifeshaper]);

    it('tells the family page who may change Levels, and offers the church\'s Levels', function (): void {
        ['church' => $church, 'dxb' => $dxb, 'amara' => $amara] = kidsChurch();

        actingAs(staffMember(RoleEnum::Admin, atChurch($church)))->get("/ccf/families/{$amara->family_id}")
            ->assertInertia(fn (AssertableInertia $p) => $p->where('canSetLevel', true)->has('levels', 4)
                ->where('levels.1', ['level' => 2, 'name' => 'Level 2'])
                ->where('family.children.0.level', 'Level 2'));
        actingAs(staffMember(RoleEnum::Admin, atSatellite($dxb)))->get("/ccf/families/{$amara->family_id}")
            ->assertInertia(fn (AssertableInertia $p) => $p->where('canSetLevel', false)->where('levels', []));
    });
});

it('shows the children list 50 at a time, filters and all (Roy 2026-10-16)', function (): void {
    ['church' => $church, 'dxb' => $dxb] = kidsChurch();
    $family = familyAt($dxb);
    Child::factory()->count(58)->for($family)->create(['church_id' => $family->church_id, 'status' => ChildStatusEnum::Active]);
    $admin = staffMember(RoleEnum::Admin, atChurch($church));

    actingAs($admin)->get('/ccf/children?status=active')->assertInertia(fn (AssertableInertia $p) => $p
        ->has('children', 50)->where('pagination.total', 59)->where('pagination.page', 1)->where('pagination.lastPage', 2));
    actingAs($admin)->get('/ccf/children?status=active&page=2')->assertInertia(fn (AssertableInertia $p) => $p
        ->has('children', 9)->where('pagination.page', 2));
});
