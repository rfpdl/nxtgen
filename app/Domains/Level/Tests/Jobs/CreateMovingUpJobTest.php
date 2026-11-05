<?php

declare(strict_types=1);

use App\Data\Models\MovingUpChild;
use App\Data\Models\User;
use App\Domains\Level\Jobs\CreateMovingUpJob;
use App\Enums\ChildStatusEnum;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* A Moving-Up remembers each child it moves — Level and status before and after — so it can be put back. */

it('keeps each child with where they were and where Moving-Up takes them, saying who made it', function (): void {
    $church = church();
    $family = familyAt(satelliteOf($church));
    $two = childOf($family, ['level' => 2]);
    $top = childOf($family, ['level' => 4]);
    $admin = User::factory()->create();

    $movingUp = (new CreateMovingUpJob(church: $church, children: [$two, $top], by: $admin))->handle();

    expect([$movingUp->church_id, $movingUp->made_by])->toBe([$church->id, $admin->id])
        ->and($movingUp->children->pluck('id')->all())->toBe($movingUp->children()->orderBy('id')->pluck('id')->all())
        ->and($movingUp->children()->orderBy('id')->get()->map(fn (MovingUpChild $m): array => [
            $m->church_id, $m->child_id, $m->from_level, $m->from_status, $m->to_level, $m->to_status, $m->put_back_at,
        ])->all())->toBe([
            [$church->id, $two->id, 2, ChildStatusEnum::Active, 3, ChildStatusEnum::Active, null],
            [$church->id, $top->id, 4, ChildStatusEnum::Active, 4, ChildStatusEnum::Graduated, null],
        ]);
});
