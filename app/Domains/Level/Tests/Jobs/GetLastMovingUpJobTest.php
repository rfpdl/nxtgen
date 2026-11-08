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

it('finds the satellite\'s latest Moving-Up, with its children — not another satellite\'s, run later', function (): void {
    $church = church();
    $dubai = satelliteOf($church);
    $child = childOf(familyAt($dubai), ['level' => 1]);
    $admin = User::factory()->create();
    (new CreateMovingUpJob(satellite: $dubai, children: [$child], by: $admin))->handle();
    travel(1)->days();
    $last = (new CreateMovingUpJob(satellite: $dubai, children: [$child], by: $admin))->handle();
    travel(1)->days();
    (new CreateMovingUpJob(satellite: satelliteOf($church), children: [], by: $admin))->handle();

    $found = (new GetLastMovingUpJob(satellite: $dubai))->handle();

    expect($found?->id)->toBe($last->id)
        ->and($found?->children->pluck('child_id')->all())->toBe([$child->id]);
});

it('is none for a satellite that has never moved up', function (): void {
    expect((new GetLastMovingUpJob(satellite: satelliteOf(church())))->handle())->toBeNull();
});
