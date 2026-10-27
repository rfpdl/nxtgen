<?php

declare(strict_types=1);

use App\Data\Models\CheckIn;
use App\Domains\CheckIn\Jobs\GetCheckedInChildrenJob;

use function Tests\Support\checkedIn;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;

/* Who is in a Session right now — each child's Check-in, newest first, for the door's list, ✓, Undo and count. */

it('lists the Check-ins in the Session newest first, leaving out an undone one and another Session\'s', function (): void {
    $satellite = satelliteOf(church());
    $family = familyAt($satellite);
    $class = scheduleAt($satellite);
    $session = sessionOf($class);
    $amara = childOf($family, ['level' => 2]);
    $ben = childOf($family, ['level' => 1]);
    $noah = childOf($family, ['level' => 3]);
    $zoe = childOf($family, ['level' => 1]);
    $amaraIn = checkedIn($session, $amara, ['checked_in_at' => '2026-10-31 09:05:00']);
    $benIn = checkedIn($session, $ben, ['checked_in_at' => '2026-10-31 09:10:00']);
    $zoeIn = checkedIn($session, $zoe, ['checked_in_at' => '2026-10-31 09:10:00']);
    checkedIn($session, $noah, ['undone_at' => now()]);
    checkedIn(sessionOf($class, '2026-11-07'), $noah);

    $in = (new GetCheckedInChildrenJob(session: $session))->handle();

    expect(array_map(fn (CheckIn $c): int => $c->id, $in))->toBe([$zoeIn->id, $benIn->id, $amaraIn->id])
        ->and($in[0]->relationLoaded('child'))->toBeTrue()
        ->and($in[0]->relationLoaded('squad'))->toBeTrue();
});
