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

it('lists the satellite\'s active children with a Level, by Level then name (Moving-Up per satellite, DECISIONS 2026-11-08)', function (): void {
    $dubai = satelliteOf(church());
    $zed = childOf(familyAt($dubai), ['level' => 2, 'last_name' => 'Abad', 'first_name' => 'Zed']);
    $amy = childOf(familyAt($dubai), ['level' => 2, 'last_name' => 'Abad', 'first_name' => 'Amy']);
    $top = childOf(familyAt($dubai), ['level' => 4, 'last_name' => 'Cruz']);
    $one = childOf(familyAt($dubai), ['level' => 1, 'last_name' => 'Yu']);

    $found = (new GetMovingUpChildrenJob(satellite: $dubai))->handle();

    expect(array_map(fn (Child $c): int => $c->id, $found))->toBe([$one->id, $amy->id, $zed->id, $top->id]);
});

it('leaves out inactive, graduated and no-Level children, other satellites\' and other churches\'', function (): void {
    $church = church();
    $dubai = satelliteOf($church);
    $family = familyAt($dubai);
    childOf($family, ['level' => 2, 'status' => ChildStatusEnum::Inactive]);
    childOf($family, ['level' => 4, 'status' => ChildStatusEnum::Graduated]);
    childOf($family, ['level' => null]);
    childOf(familyAt(satelliteOf($church)), ['level' => 2]);
    childOf(familyAt(satelliteOf(church())), ['level' => 2]);

    expect((new GetMovingUpChildrenJob(satellite: $dubai))->handle())->toBe([]);
});
