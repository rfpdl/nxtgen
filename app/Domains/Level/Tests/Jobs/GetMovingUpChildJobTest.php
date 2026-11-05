<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\Level\Jobs\CreateMovingUpJob;
use App\Domains\Level\Jobs\GetMovingUpChildJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* "Put back" names a child of one Moving-Up: found only on that Moving-Up, in this church. */

it('finds the child on this Moving-Up', function (): void {
    $church = church();
    $child = childOf(familyAt(satelliteOf($church)), ['level' => 1]);
    $movingUp = (new CreateMovingUpJob(church: $church, children: [$child], by: User::factory()->create()))->handle();

    expect((new GetMovingUpChildJob(church: $church, movingUp: $movingUp, child: $child))->handle()->child_id)->toBe($child->id);
});

it('finds no child that Moving-Up did not move', function (): void {
    $church = church();
    $family = familyAt(satelliteOf($church));
    $movingUp = (new CreateMovingUpJob(church: $church, children: [childOf($family, ['level' => 1])], by: User::factory()->create()))->handle();

    expect(fn () => (new GetMovingUpChildJob(church: $church, movingUp: $movingUp, child: childOf($family, ['level' => 1])))->handle())
        ->toThrow(ModelNotFoundException::class);
});
