<?php

declare(strict_types=1);

use App\Data\Models\Child;
use App\Data\Models\MovingUp;
use App\Data\Models\Note;
use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Enums\ChildStatusEnum;
use App\Enums\PutBackResultEnum;
use App\Operations\Level\MoveChildrenUpOperation;
use App\Operations\Level\PutBackChildOperation;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/*
 * Put back (Roy 2026-11-06: "can we undo specific student as well?"): one child of the last Moving-Up goes back to the
 * Level and status they had — only if nobody has changed them since.
 */

function movedUp(Child ...$children): MovingUp
{
    $movingUp = (new MoveChildrenUpOperation(
        satellite: $children[0]->family->homeSatellite,
        expected: collect($children)->mapWithKeys(fn (Child $c): array => [$c->id => (int) $c->level])->all(),
        by: User::factory()->create(),
    ))->handle();
    expect($movingUp)->not->toBeNull();

    return $movingUp ?? new MovingUp;
}

function putBack(MovingUp $movingUp, Child $child, ?User $by = null): PutBackResultEnum
{
    return (new PutBackChildOperation(satellite: Satellite::query()->findOrFail($movingUp->satellite_id), movingUp: $movingUp, childId: $child->id, by: $by ?? User::factory()->create()))->handle();
}

it('puts a moved child back to their Level, and a graduated one back to Active, marking who and when', function (): void {
    $family = familyAt(satelliteOf(church()));
    $one = childOf($family, ['level' => 1, 'nickname' => 'Amara']);
    $top = childOf($family, ['level' => 4, 'nickname' => 'Gabe']);
    $movingUp = movedUp($one, $top);
    $admin = User::factory()->create();

    expect(putBack($movingUp, $one, $admin))->toBe(PutBackResultEnum::PutBack)
        ->and(putBack($movingUp, $top, $admin))->toBe(PutBackResultEnum::PutBack)
        ->and([$one->fresh()?->level, $one->fresh()?->status])->toBe([1, ChildStatusEnum::Active])
        ->and([$top->fresh()?->level, $top->fresh()?->status])->toBe([4, ChildStatusEnum::Active])
        ->and($movingUp->children()->pluck('put_back_by')->all())->toBe([$admin->id, $admin->id])
        ->and(Note::query()->latest('id')->limit(2)->pluck('body')->all())->toBe([
            'Gabe: status changed from Graduated to Active.',
            'Amara: Level changed from Level 2 to Level 1.',
        ]);
});

it('leaves only that child put back — the others stay moved up', function (): void {
    $family = familyAt(satelliteOf(church()));
    $amara = childOf($family, ['level' => 1]);
    $ben = childOf($family, ['level' => 2]);
    $movingUp = movedUp($amara, $ben);

    putBack($movingUp, $amara);

    expect([$amara->fresh()?->level, $ben->fresh()?->level])->toBe([1, 3]);
});

it('leaves a child whose Level or status someone changed since Moving-Up', function (Closure $change): void {
    $child = childOf(familyAt(satelliteOf(church())), ['level' => 1]);
    $movingUp = movedUp($child);
    $change($child->refresh());
    $since = [$child->level, $child->status];

    expect(putBack($movingUp, $child))->toBe(PutBackResultEnum::ChangedSince)
        ->and([$child->fresh()?->level, $child->fresh()?->status])->toBe($since)
        ->and($movingUp->children()->sole()->put_back_at)->toBeNull();
})->with([
    'a Level corrected' => [fn (Child $c) => $c->forceFill(['level' => 3])->save()],
    'made inactive' => [fn (Child $c) => $c->forceFill(['status' => ChildStatusEnum::Inactive])->save()],
]);

it('puts a child back only once', function (): void {
    $child = childOf(familyAt(satelliteOf(church())), ['level' => 1]);
    $movingUp = movedUp($child);

    expect(putBack($movingUp, $child))->toBe(PutBackResultEnum::PutBack)
        ->and(putBack($movingUp, $child))->toBe(PutBackResultEnum::AlreadyPutBack)
        ->and($child->fresh()?->level)->toBe(1);
});

it('leaves a child whose family moved to another satellite since — the other satellite\'s now (Astra, issue 06)', function (): void {
    $church = church();
    $family = familyAt(satelliteOf($church));
    $amara = childOf($family, ['level' => 1]);
    $movingUp = movedUp($amara);
    $family->forceFill(['home_satellite_id' => satelliteOf($church)->id])->save();

    expect(putBack($movingUp, $amara))->toBe(PutBackResultEnum::Elsewhere)
        ->and($amara->fresh()?->level)->toBe(2);
});
