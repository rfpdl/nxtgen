<?php

declare(strict_types=1);

use App\Data\Models\Child;
use App\Domains\Level\Jobs\GetMovingUpChildrenJob;
use App\Enums\ChildStatusEnum;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* Who Moving-Up moves: this church's Active children with a Level, every satellite, lowest Level first. */

it('lists the church\'s active children with a Level, every satellite, by Level then name', function (): void {
    $church = church();
    $zed = childOf(familyAt(satelliteOf($church)), ['level' => 2, 'last_name' => 'Abad', 'first_name' => 'Zed']);
    $amy = childOf(familyAt(satelliteOf($church)), ['level' => 2, 'last_name' => 'Abad', 'first_name' => 'Amy']);
    $top = childOf(familyAt(satelliteOf($church)), ['level' => 4, 'last_name' => 'Cruz']);
    $one = childOf(familyAt(satelliteOf($church)), ['level' => 1, 'last_name' => 'Yu']);

    $found = (new GetMovingUpChildrenJob(church: $church))->handle();

    expect(array_map(fn (Child $c): int => $c->id, $found))->toBe([$one->id, $amy->id, $zed->id, $top->id]);
});

it('leaves out inactive, graduated and no-Level children, and other churches\'', function (): void {
    $church = church();
    $family = familyAt(satelliteOf($church));
    childOf($family, ['level' => 2, 'status' => ChildStatusEnum::Inactive]);
    childOf($family, ['level' => 4, 'status' => ChildStatusEnum::Graduated]);
    childOf($family, ['level' => null]);
    childOf(familyAt(satelliteOf(church())), ['level' => 2]);

    expect((new GetMovingUpChildrenJob(church: $church))->handle())->toBe([]);
});
