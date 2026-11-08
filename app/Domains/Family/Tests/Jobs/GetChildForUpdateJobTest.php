<?php

declare(strict_types=1);

use App\Domains\Family\Jobs\GetChildForUpdateJob;
use Illuminate\Support\Facades\DB;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/*
 * A child read under lock, as they are now — so a Level fix or a status change records where the child really was,
 * even when a Moving-Up committed since the page loaded them (issue 06 review).
 */

it('reads the child as they are now, with their family, inside a transaction', function (): void {
    $child = childOf(familyAt(satelliteOf(church())), ['level' => 2]);
    $child->newQuery()->whereKey($child->id)->update(['level' => 3]);

    $found = DB::transaction(fn () => (new GetChildForUpdateJob(child: $child))->handle());

    expect($found->level)->toBe(3)
        ->and($found->relationLoaded('family'))->toBeTrue()
        ->and($child->level)->toBe(2);
});
