<?php

declare(strict_types=1);

use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Data\Models\MovingUp;
use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Enums\ChildStatusEnum;
use App\Enums\RoleEnum;
use App\Operations\Level\MoveChildrenUpOperation;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travel;
use function Tests\Support\atChurch;
use function Tests\Support\atSatellite;
use function Tests\Support\atTheSameTime;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;
use function Tests\Support\staffMember;
use function Tests\Support\withCommittedWorld;

/*
 * Moving-Up (TODO #27; DECISIONS 2026-11-06): the yearly step to the next Level, run by a church admin only. The page
 * lists every child who will move, ticked, with their new Level; the admin unticks anyone who stays. The last
 * Moving-Up can be put back — one child, or everyone.
 */

/** @return array{church: Church, dxb: Satellite, house: Satellite, admin: User} */
function movingUpChurch(): array
{
    $church = church(['slug' => 'ccf', 'name' => 'CCF']);

    return [
        'church' => $church,
        'dxb' => satelliteOf($church, ['name' => 'CCF DXB', 'slug' => 'dxb']),
        'house' => satelliteOf($church, ['name' => 'House Church', 'slug' => 'house']),
        'admin' => staffMember(RoleEnum::Admin, atChurch($church), ['name' => 'Ana Admin']),
    ];
}

/**
 * The form's answer: each ticked child with the Level the page showed.
 *
 * @return array{children: list<array{child_id: int, level: int|null}>}
 */
function tickedToMoveUp(Child ...$children): array
{
    return ['children' => array_map(fn (Child $c): array => ['child_id' => $c->id, 'level' => $c->level], array_values($children))];
}

function lastMovingUp(): MovingUp
{
    return MovingUp::query()->latest('id')->firstOrFail();
}

describe('the Moving-Up page', function (): void {
    it('shows a church admin every active child with a Level, lowest first, with where each goes, and how many have no Level @story#154', function (): void {
        ['dxb' => $dxb, 'house' => $house, 'admin' => $admin] = movingUpChurch();
        childOf(familyAt($dxb), ['first_name' => 'Gabe', 'last_name' => 'Cruz', 'nickname' => 'Gabe', 'level' => 4]);
        childOf(familyAt($house), ['first_name' => 'Pia', 'last_name' => 'Villa', 'nickname' => 'Pia', 'level' => 1]);
        childOf(familyAt($dxb), ['level' => null]);
        childOf(familyAt($dxb), ['level' => 2, 'status' => ChildStatusEnum::Inactive]);

        actingAs($admin)->get('/dxb/admin/moving-up')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Church/MovingUp/Index')
            ->where('children.0.nickname', 'Pia')->where('children.0.satellite', 'House Church')
            ->where('children.0.level.name', 'Level 1')->where('children.0.moves_to', 'Level 2')
            ->where('children.1.nickname', 'Gabe')->where('children.1.moves_to', 'Graduated')
            ->has('children', 2)
            ->where('without_level', 1)
            ->where('last', null));
    });

    it('shows the last Moving-Up: when, by whom, each child from and to, and who was put back @story#154', function (): void {
        ['dxb' => $dxb, 'admin' => $admin] = movingUpChurch();
        $amara = childOf(familyAt($dxb), ['nickname' => 'Amara', 'level' => 1]);
        $ben = childOf(familyAt($dxb), ['nickname' => 'Ben', 'level' => 4]);
        actingAs($admin)->post('/dxb/admin/moving-up', tickedToMoveUp($amara, $ben));
        actingAs($admin)->put('/dxb/admin/moving-up/'.lastMovingUp()->id."/children/{$amara->id}/put-back");

        actingAs($admin)->get('/dxb/admin/moving-up')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('last.id', lastMovingUp()->id)
            ->where('last.made_by', 'Ana Admin')
            ->where('last.children.0.nickname', 'Amara')->where('last.children.0.from', 'Level 1')->where('last.children.0.to', 'Level 2')
            ->where('last.children.0.put_back', fn (?string $by): bool => is_string($by) && str_starts_with($by, 'Ana Admin · '))
            ->where('last.children.1.nickname', 'Ben')->where('last.children.1.to', 'Graduated')->where('last.children.1.put_back', null));
    });

    it('says when the last Moving-Up is recent, so another would move everyone up a second Level @story#154', function (): void {
        ['dxb' => $dxb, 'admin' => $admin] = movingUpChurch();
        actingAs($admin)->post('/dxb/admin/moving-up', tickedToMoveUp(childOf(familyAt($dxb), ['level' => 1])));

        actingAs($admin)->get('/dxb/admin/moving-up')->assertInertia(fn (AssertableInertia $page) => $page->where('last.is_recent', true));
        travel(31)->days();
        actingAs($admin)->get('/dxb/admin/moving-up')->assertInertia(fn (AssertableInertia $page) => $page->where('last.is_recent', false));
    });

    it('puts back no child that is not on the last Moving-Up @story#159', function (): void {
        ['dxb' => $dxb, 'admin' => $admin] = movingUpChurch();
        actingAs($admin)->post('/dxb/admin/moving-up', tickedToMoveUp(childOf(familyAt($dxb), ['level' => 1])));
        $other = childOf(familyAt($dxb), ['level' => 1]);

        actingAs($admin)->put('/dxb/admin/moving-up/'.lastMovingUp()->id."/children/{$other->id}/put-back")->assertNotFound();
    });

    it('refuses a child ticked twice @story#157', function (): void {
        ['dxb' => $dxb, 'admin' => $admin] = movingUpChurch();
        $amara = childOf(familyAt($dxb), ['level' => 1]);

        actingAs($admin)->post('/dxb/admin/moving-up', ['children' => [['child_id' => $amara->id, 'level' => 1], ['child_id' => $amara->id, 'level' => 1]]])
            ->assertSessionHasErrors('children.1.child_id');

        expect($amara->fresh()?->level)->toBe(1);
    });

    it('offers the way there (a button on Children) to a church admin only', function (): void {
        ['dxb' => $dxb, 'admin' => $admin] = movingUpChurch();

        actingAs($admin)->get('/dxb/serve/families')->assertInertia(fn (AssertableInertia $page) => $page->where('church_nav.can_move_up', true));
        actingAs(staffMember(RoleEnum::Admin, atSatellite($dxb)))->get('/dxb/serve/families')->assertInertia(fn (AssertableInertia $page) => $page->where('church_nav.can_move_up', false));
    });

    it('is not there for a satellite admin or a Lifeshaper @story#155', function (): void {
        ['dxb' => $dxb] = movingUpChurch();

        actingAs(staffMember(RoleEnum::Admin, atSatellite($dxb)))->get('/dxb/admin/moving-up')->assertNotFound();
        // DECISIONS 2026-11-08: a Lifeshaper opening an Admin page is sent to the door.
        actingAs(staffMember(RoleEnum::Lifeshaper, atSatellite($dxb)))->get('/dxb/admin/moving-up')->assertRedirect('/dxb/serve/door');
    });
});

describe('moving up', function (): void {
    it('moves the ticked children up a Level — from the top Level to Graduated — and says how many @story#156', function (): void {
        ['dxb' => $dxb, 'admin' => $admin] = movingUpChurch();
        $amara = childOf(familyAt($dxb), ['level' => 1]);
        $gabe = childOf(familyAt($dxb), ['level' => 4]);

        actingAs($admin)->from('/dxb/admin/moving-up')->post('/dxb/admin/moving-up', tickedToMoveUp($amara, $gabe))
            ->assertRedirect('/dxb/admin/moving-up')->assertSessionHas('success', '2 children moved up; 1 Graduated.');

        expect($amara->fresh()?->level)->toBe(2)
            ->and($gabe->fresh()?->status)->toBe(ChildStatusEnum::Graduated)
            ->and(lastMovingUp()->made_by)->toBe($admin->id);
    });

    it('leaves a child the admin unticked where they are @story#156', function (): void {
        ['dxb' => $dxb, 'admin' => $admin] = movingUpChurch();
        $amara = childOf(familyAt($dxb), ['level' => 1]);
        $stays = childOf(familyAt($dxb), ['level' => 1]);

        actingAs($admin)->post('/dxb/admin/moving-up', tickedToMoveUp($amara))->assertSessionHas('success', '1 child moved up.');

        expect([$amara->fresh()?->level, $stays->fresh()?->level])->toBe([2, 1]);
    });

    it('moves nobody when a child changed since the page was opened, and says to look again @story#157', function (): void {
        ['dxb' => $dxb, 'admin' => $admin] = movingUpChurch();
        $amara = childOf(familyAt($dxb), ['level' => 1]);
        $ben = childOf(familyAt($dxb), ['level' => 2]);
        $form = tickedToMoveUp($amara, $ben);
        $ben->forceFill(['level' => 3])->save();

        actingAs($admin)->post('/dxb/admin/moving-up', $form)
            ->assertSessionHas('error', 'Some children changed since this page was opened, so nobody moved. Please look again.');

        expect($amara->fresh()?->level)->toBe(1)
            ->and(MovingUp::query()->count())->toBe(0);
    });

    it('asks for at least one child @story#157', function (): void {
        ['admin' => $admin] = movingUpChurch();

        actingAs($admin)->post('/dxb/admin/moving-up', ['children' => []])->assertSessionHasErrors(['children' => 'Please tick at least one child to move up.']);
    });

    it('never moves another church\'s child @story#157', function (): void {
        ['admin' => $admin] = movingUpChurch();
        $theirs = childOf(familyAt(satelliteOf(church())), ['level' => 1]);

        actingAs($admin)->post('/dxb/admin/moving-up', tickedToMoveUp($theirs))->assertNotFound();

        expect($theirs->fresh()?->level)->toBe(1);
    });

    it('is never run by a satellite admin @story#157', function (): void {
        ['dxb' => $dxb] = movingUpChurch();
        $child = childOf(familyAt($dxb), ['level' => 1]);

        actingAs(staffMember(RoleEnum::Admin, atSatellite($dxb)))->post('/dxb/admin/moving-up', tickedToMoveUp($child))->assertNotFound();

        expect($child->fresh()?->level)->toBe(1);
    });
});

describe('putting one child back', function (): void {
    it('puts the child back to the Level they had and says so @story#158', function (): void {
        ['dxb' => $dxb, 'admin' => $admin] = movingUpChurch();
        $amara = childOf(familyAt($dxb), ['nickname' => 'Amara', 'level' => 1]);
        actingAs($admin)->post('/dxb/admin/moving-up', tickedToMoveUp($amara));

        actingAs($admin)->put('/dxb/admin/moving-up/'.lastMovingUp()->id."/children/{$amara->id}/put-back")
            ->assertSessionHas('success', 'Amara is put back: Level 1.');

        expect($amara->fresh()?->level)->toBe(1);
    });

    it('puts a graduated child back to Active @story#158', function (): void {
        ['dxb' => $dxb, 'admin' => $admin] = movingUpChurch();
        $gabe = childOf(familyAt($dxb), ['nickname' => 'Gabe', 'level' => 4]);
        actingAs($admin)->post('/dxb/admin/moving-up', tickedToMoveUp($gabe));

        actingAs($admin)->put('/dxb/admin/moving-up/'.lastMovingUp()->id."/children/{$gabe->id}/put-back")
            ->assertSessionHas('success', 'Gabe is put back: Level 4.');

        expect($gabe->fresh()?->status)->toBe(ChildStatusEnum::Active);
    });

    it('leaves a child someone changed since, and says why @story#159', function (): void {
        ['dxb' => $dxb, 'admin' => $admin] = movingUpChurch();
        $amara = childOf(familyAt($dxb), ['nickname' => 'Amara', 'level' => 1]);
        actingAs($admin)->post('/dxb/admin/moving-up', tickedToMoveUp($amara));
        $amara->refresh()->forceFill(['level' => 4])->save();

        actingAs($admin)->put('/dxb/admin/moving-up/'.lastMovingUp()->id."/children/{$amara->id}/put-back")
            ->assertSessionHas('error', 'Amara has a different Level or status since Moving-Up, so was left as they are.');

        expect($amara->fresh()?->level)->toBe(4);
    });

    it('puts back only from the last Moving-Up @story#159', function (): void {
        ['dxb' => $dxb, 'admin' => $admin] = movingUpChurch();
        $amara = childOf(familyAt($dxb), ['level' => 1]);
        actingAs($admin)->post('/dxb/admin/moving-up', tickedToMoveUp($amara));
        $first = lastMovingUp();
        travel(1)->days();
        actingAs($admin)->post('/dxb/admin/moving-up', tickedToMoveUp($amara->fresh() ?? $amara));

        actingAs($admin)->put("/dxb/admin/moving-up/{$first->id}/children/{$amara->id}/put-back")->assertNotFound();

        expect($amara->fresh()?->level)->toBe(3);
    });

    it('is never done by a satellite admin @story#159', function (): void {
        ['dxb' => $dxb, 'admin' => $admin] = movingUpChurch();
        $amara = childOf(familyAt($dxb), ['level' => 1]);
        actingAs($admin)->post('/dxb/admin/moving-up', tickedToMoveUp($amara));

        actingAs(staffMember(RoleEnum::Admin, atSatellite($dxb)))
            ->put('/dxb/admin/moving-up/'.lastMovingUp()->id."/children/{$amara->id}/put-back")->assertNotFound();

        expect($amara->fresh()?->level)->toBe(2);
    });
});

describe('putting everyone back', function (): void {
    it('puts back every child not yet put back, and names any left because they changed since @story#160', function (): void {
        ['dxb' => $dxb, 'admin' => $admin] = movingUpChurch();
        $amara = childOf(familyAt($dxb), ['nickname' => 'Amara', 'level' => 1]);
        $ben = childOf(familyAt($dxb), ['nickname' => 'Ben', 'level' => 2]);
        $lia = childOf(familyAt($dxb), ['nickname' => 'Lia', 'level' => 3]);
        actingAs($admin)->post('/dxb/admin/moving-up', tickedToMoveUp($amara, $ben, $lia));
        $movingUp = lastMovingUp();
        actingAs($admin)->put("/dxb/admin/moving-up/{$movingUp->id}/children/{$amara->id}/put-back");
        $lia->refresh()->forceFill(['status' => ChildStatusEnum::Inactive])->save();

        actingAs($admin)->put("/dxb/admin/moving-up/{$movingUp->id}/put-back")
            ->assertSessionHas('success', '1 child put back. Left as they are (changed since Moving-Up): Lia.');

        expect([$amara->fresh()?->level, $ben->fresh()?->level, $lia->fresh()?->level])->toBe([1, 2, 4]);
    });

    it('says it as an error when every child left had changed since @story#160', function (): void {
        ['dxb' => $dxb, 'admin' => $admin] = movingUpChurch();
        $lia = childOf(familyAt($dxb), ['nickname' => 'Lia', 'level' => 1]);
        actingAs($admin)->post('/dxb/admin/moving-up', tickedToMoveUp($lia));
        $lia->refresh()->forceFill(['level' => 4])->save();

        actingAs($admin)->put('/dxb/admin/moving-up/'.lastMovingUp()->id.'/put-back')
            ->assertSessionHas('error', '0 children put back. Left as they are (changed since Moving-Up): Lia.');
    });

    it('says so when everyone was already put back @story#160', function (): void {
        ['dxb' => $dxb, 'admin' => $admin] = movingUpChurch();
        $amara = childOf(familyAt($dxb), ['level' => 1]);
        actingAs($admin)->post('/dxb/admin/moving-up', tickedToMoveUp($amara));
        actingAs($admin)->put('/dxb/admin/moving-up/'.lastMovingUp()->id.'/put-back');

        actingAs($admin)->put('/dxb/admin/moving-up/'.lastMovingUp()->id.'/put-back')->assertSessionHas('success', 'Everyone was already put back.');
    });

    it('puts back only the last Moving-Up, and never for a satellite admin @story#161', function (): void {
        ['dxb' => $dxb, 'admin' => $admin] = movingUpChurch();
        $amara = childOf(familyAt($dxb), ['level' => 1]);
        actingAs($admin)->post('/dxb/admin/moving-up', tickedToMoveUp($amara));
        $first = lastMovingUp();

        actingAs(staffMember(RoleEnum::Admin, atSatellite($dxb)))->put("/dxb/admin/moving-up/{$first->id}/put-back")->assertNotFound();
        travel(1)->days();
        actingAs($admin)->post('/dxb/admin/moving-up', tickedToMoveUp($amara->fresh() ?? $amara));
        actingAs($admin)->put("/dxb/admin/moving-up/{$first->id}/put-back")->assertNotFound();

        expect($amara->fresh()?->level)->toBe(3);
    });
});

describe('putting back while a new Moving-Up runs', function (): void {
    it('never puts back a Moving-Up that a new one has just replaced (Astra) — two connections @story#159', function (): void {
        ['church' => $church, 'dxb' => $dxb, 'admin' => $admin] = movingUpChurch();
        $amara = childOf(familyAt($dxb), ['level' => 1]);
        $ben = childOf(familyAt($dxb), ['level' => 1]);
        actingAs($admin)->post('/dxb/admin/moving-up', tickedToMoveUp($amara));
        $first = lastMovingUp();

        withCommittedWorld(function () use ($church, $admin, $ben, $amara, $first): void {
            [, $status] = atTheSameTime(
                // A new Moving-Up (Ben only) holds the church while it saves.
                function () use ($church, $admin, $ben): bool {
                    DB::transaction(function () use ($church, $admin, $ben): void {
                        (new MoveChildrenUpOperation(church: $church, expected: [$ben->id => 1], by: $admin))->handle();
                        usleep(1_000_000);
                    });

                    return true;
                },
                function () use ($admin, $amara, $first): int {
                    usleep(150_000);

                    return actingAs($admin)->put("/dxb/admin/moving-up/{$first->id}/children/{$amara->id}/put-back")->status();
                },
            );

            expect($status)->toBe(404)
                ->and($amara->fresh()?->level)->toBe(2);
        });
    });
});
