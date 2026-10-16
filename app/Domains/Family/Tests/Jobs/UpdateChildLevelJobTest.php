<?php

declare(strict_types=1);

use App\Data\Models\Child;
use App\Data\Models\User;
use App\Domains\Family\Jobs\UpdateChildLevelJob;

/* A church admin's correction of a child's Level outside Moving-Up: the Level, who set it and when. */

it('puts the child at the Level, recording who and when', function (): void {
    $child = Child::factory()->create(['level' => 1]);
    $admin = User::factory()->create();

    $updated = (new UpdateChildLevelJob(child: $child, level: 2, by: $admin))->handle();

    $child->refresh();
    expect($updated->level)->toBe(2)
        ->and($child->level)->toBe(2)
        ->and($child->level_set_by)->toBe($admin->id)
        ->and($child->level_set_at)->not->toBeNull();
});
