<?php

declare(strict_types=1);

use App\Domains\CheckIn\Jobs\GetCheckedInChildrenJob;

use function Tests\Support\checkedIn;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;

/* Who is in a Session right now — each child's Check-in, for the door's ✓ and Undo, and its count. */

it('lists the children in the Session, keyed by child, leaving out an undone one and another Session\'s', function (): void {
    $satellite = satelliteOf(church());
    $family = familyAt($satellite);
    $class = scheduleAt($satellite);
    $session = sessionOf($class);
    $amara = childOf($family, ['level' => 2]);
    $ben = childOf($family, ['level' => 1]);
    $noah = childOf($family, ['level' => 3]);
    $amaraIn = checkedIn($session, $amara);
    $benIn = checkedIn($session, $ben);
    checkedIn($session, $noah, ['undone_at' => now()]);
    checkedIn(sessionOf($class, '2026-11-07'), $noah);

    expect((new GetCheckedInChildrenJob(session: $session))->handle())->toBe([$amara->id => $amaraIn->id, $ben->id => $benIn->id]);
});
