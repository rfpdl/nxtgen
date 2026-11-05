<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\Level\Jobs\CreateMovingUpJob;
use App\Domains\Level\Jobs\UpdateMovingUpChildAsPutBackJob;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* A child put back is marked so on the Moving-Up: who and when. */

it('marks the child put back, saying who and when', function (): void {
    $church = church();
    $child = childOf(familyAt(satelliteOf($church)), ['level' => 1]);
    $movingUp = (new CreateMovingUpJob(church: $church, children: [$child], by: User::factory()->create()))->handle();
    $admin = User::factory()->create();

    (new UpdateMovingUpChildAsPutBackJob(moved: $movingUp->children()->sole(), by: $admin))->handle();

    $moved = $movingUp->children()->sole();
    expect($moved->put_back_by)->toBe($admin->id)
        ->and($moved->put_back_at)->not->toBeNull();
});
