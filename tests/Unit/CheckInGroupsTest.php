<?php

declare(strict_types=1);

use App\Data\Models\CheckIn;
use App\Foundation\Support\CheckInGroups;

/* Check-ins grouped by Squad, the fold behind Attendance's and Who's in's Squads. */

it('groups Check-ins by their Squad, keeping the order they came in', function (): void {
    $in = fn (int $id, int $squad): CheckIn => (new CheckIn)->forceFill(['id' => $id, 'squad_id' => $squad]);

    $groups = CheckInGroups::bySquad([$in(1, 7), $in(2, 9), $in(3, 7), $in(4, 5)]);

    expect(array_map(fn (array $g): array => array_map(fn (CheckIn $c): int => $c->id, $g), $groups))
        ->toBe([[1, 3], [2], [4]]);
});

it('has no groups for no Check-ins', function (): void {
    expect(CheckInGroups::bySquad([]))->toBe([]);
});
