<?php

declare(strict_types=1);

use App\Data\Models\Child;
use App\Data\Models\MovingUp;
use App\Data\Models\Note;
use App\Data\Models\User;
use App\Domains\Family\Jobs\UpdateChildLevelJob;
use App\Enums\ChildStatusEnum;
use App\Operations\Level\MoveChildrenUpOperation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

use function Tests\Support\atTheSameTime;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;
use function Tests\Support\withCommittedWorld;

/*
 * Moving-Up (DECISIONS 2026-11-06): the children the admin left ticked, each with the Level the page showed, go to the
 * next Level — from the top Level they become Graduated — all together, kept so it can be put back. If any of them
 * changed since the page was opened (another admin, a double tap), nothing moves.
 */

function moveUp(Child ...$children): ?MovingUp
{
    return (new MoveChildrenUpOperation(
        church: $children[0]->family->church,
        expected: collect($children)->mapWithKeys(fn (Child $c): array => [$c->id => (int) $c->level])->all(),
        by: User::factory()->create(),
    ))->handle();
}

it('moves each child to the next Level, and from the top Level to Graduated, noting it on their history', function (): void {
    $family = familyAt(satelliteOf(church()));
    $one = childOf($family, ['level' => 1, 'nickname' => 'Amara']);
    $top = childOf($family, ['level' => 4, 'nickname' => 'Gabe']);

    $movingUp = moveUp($one, $top);

    expect([$one->fresh()?->level, $one->fresh()?->status])->toBe([2, ChildStatusEnum::Active])
        ->and([$top->fresh()?->level, $top->fresh()?->status])->toBe([4, ChildStatusEnum::Graduated])
        ->and($movingUp?->children()->count())->toBe(2)
        ->and(Note::query()->orderBy('id')->pluck('body')->all())->toBe([
            'Amara: Level changed from Level 1 to Level 2.',
            'Gabe: status changed from Active to Graduated.',
        ]);
});

it('moves nothing when a child is no longer at the Level the page showed', function (): void {
    $family = familyAt(satelliteOf(church()));
    $amara = childOf($family, ['level' => 1]);
    $ben = childOf($family, ['level' => 2]);
    $ben->forceFill(['level' => 3])->save(); // another admin, after the page was opened

    $movingUp = (new MoveChildrenUpOperation(church: $family->church, expected: [$amara->id => 1, $ben->id => 2], by: User::factory()->create()))->handle();

    expect($movingUp)->toBeNull()
        ->and($amara->fresh()?->level)->toBe(1)
        ->and(MovingUp::query()->count())->toBe(0);
});

it('moves nothing twice: the same page pressed again finds the children already moved', function (): void {
    $child = childOf(familyAt(satelliteOf(church())), ['level' => 1]);
    $expected = [$child->id => 1];
    $op = fn () => (new MoveChildrenUpOperation(church: $child->family->church, expected: $expected, by: User::factory()->create()))->handle();

    expect($op())->not->toBeNull()
        ->and($op())->toBeNull()
        ->and($child->fresh()?->level)->toBe(2);
});

it('moves nothing when a child is no longer active', function (ChildStatusEnum $status): void {
    $child = childOf(familyAt(satelliteOf(church())), ['level' => 2]);
    $child->forceFill(['status' => $status])->save();

    expect((new MoveChildrenUpOperation(church: $child->family->church, expected: [$child->id => 2], by: User::factory()->create()))->handle())->toBeNull()
        ->and($child->fresh()?->level)->toBe(2);
})->with([ChildStatusEnum::Inactive, ChildStatusEnum::Graduated]);

it('refuses a child of another church', function (): void {
    $theirs = childOf(familyAt(satelliteOf(church())), ['level' => 1]);

    expect(fn () => (new MoveChildrenUpOperation(church: church(), expected: [$theirs->id => 1], by: User::factory()->create()))->handle())
        ->toThrow(ModelNotFoundException::class);
    expect($theirs->fresh()?->level)->toBe(1);
});

it('never overwrites a Level an admin is correcting at that moment (Astra) — two connections', function (): void {
    $child = childOf(familyAt(satelliteOf(church())), ['level' => 1]);
    $admin = User::factory()->create();

    withCommittedWorld(function () use ($child, $admin): void {
        [, $moved] = atTheSameTime(
            // The correction to Level 3 has written but not yet committed when Moving-Up reads the child.
            function () use ($child, $admin): bool {
                DB::transaction(function () use ($child, $admin): void {
                    (new UpdateChildLevelJob(child: $child, level: 3, by: $admin))->handle();
                    usleep(1_000_000);
                });

                return true;
            },
            function () use ($child, $admin): bool {
                usleep(150_000);

                return (new MoveChildrenUpOperation(church: $child->family->church, expected: [$child->id => 1], by: $admin))->handle() !== null;
            },
        );

        expect($moved)->toBeFalse()
            ->and($child->fresh()?->level)->toBe(3)
            ->and(MovingUp::query()->count())->toBe(0);
    });
});
