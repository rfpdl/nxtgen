<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\Level\Jobs\CreateMovingUpJob;
use App\Domains\Level\Jobs\GetLastMovingUpJob;

use function Pest\Laravel\travel;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/* Only the church's last Moving-Up can be put back. */

it('finds the church\'s latest Moving-Up, with its children', function (): void {
    $church = church();
    $child = childOf(familyAt(satelliteOf($church)), ['level' => 1]);
    $admin = User::factory()->create();
    (new CreateMovingUpJob(church: $church, children: [$child], by: $admin))->handle();
    travel(1)->days();
    $last = (new CreateMovingUpJob(church: $church, children: [$child], by: $admin))->handle();
    (new CreateMovingUpJob(church: church(), children: [], by: $admin))->handle();

    $found = (new GetLastMovingUpJob(church: $church))->handle();

    expect($found?->id)->toBe($last->id)
        ->and($found?->children->pluck('child_id')->all())->toBe([$child->id]);
});

it('is none for a church that has never moved up', function (): void {
    expect((new GetLastMovingUpJob(church: church()))->handle())->toBeNull();
});
