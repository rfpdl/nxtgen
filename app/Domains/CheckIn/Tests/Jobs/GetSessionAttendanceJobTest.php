<?php

declare(strict_types=1);

use App\Data\Models\CheckIn;
use App\Data\Models\Session;
use App\Data\Models\Squad;
use App\Data\Models\User;
use App\Domains\CheckIn\Jobs\GetSessionAttendanceJob;
use App\Enums\GenderEnum;

use function Tests\Support\checkedIn;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;

/* The children checked in to a Session, for Attendance: by Squad — Level, then mixed, boys, girls — then nickname. */

/** @return list<string> "Squad: nickname by who" */
function attendanceLines(Session $session): array
{
    return array_map(
        fn (CheckIn $c): string => "{$c->squad->name()}: {$c->child->nickname} by {$c->checkedInBy?->name}",
        (new GetSessionAttendanceJob(session: $session))->handle(),
    );
}

it('lists the children in, by Squad and then nickname, with who checked them in — leaving out an undone one and another Session\'s', function (): void {
    $satellite = satelliteOf(church());
    $family = familyAt($satellite);
    $class = scheduleAt($satellite);
    $session = sessionOf($class);
    $lifeshaper = User::factory()->create(['name' => 'Liza Lifeshaper']);
    $zoe = childOf($family, ['nickname' => 'Zoe', 'level' => 2, 'gender' => GenderEnum::Girl]);
    $amara = childOf($family, ['nickname' => 'amara', 'level' => 2, 'gender' => GenderEnum::Girl]);
    $carl = childOf($family, ['nickname' => 'Carl', 'level' => 2, 'gender' => GenderEnum::Boy]);
    $ben = childOf($family, ['nickname' => 'Ben', 'level' => 1, 'gender' => GenderEnum::Boy]);
    $noah = childOf($family, ['nickname' => 'Noah', 'level' => 3, 'gender' => GenderEnum::Boy]);
    foreach ([$zoe, $amara, $carl, $ben] as $child) {
        checkedIn($session, $child, ['checked_in_by' => $lifeshaper->id]);
    }
    checkedIn($session, $noah, ['undone_at' => now()]);
    checkedIn(sessionOf($class, '2026-11-07'), $noah);

    expect(attendanceLines($session))->toBe([
        'Level 1: Ben by Liza Lifeshaper',
        'Level 2 Boys: Carl by Liza Lifeshaper',
        'Level 2 Girls: amara by Liza Lifeshaper',
        'Level 2 Girls: Zoe by Liza Lifeshaper',
    ]);
});

it('puts a Level\'s mixed Squad first, then boys, then girls — whatever order they were made or checked in', function (): void {
    $satellite = satelliteOf(church());
    $family = familyAt($satellite);
    $session = sessionOf(scheduleAt($satellite));
    $squad = fn (?GenderEnum $gender): Squad => Squad::factory()->create(['church_id' => $satellite->church_id, 'satellite_id' => $satellite->id, 'level' => 3, 'gender' => $gender]);
    $girls = $squad(GenderEnum::Girl);
    $boys = $squad(GenderEnum::Boy);
    $mixed = $squad(null);
    foreach ([[$girls, 'Gia'], [$boys, 'Bo'], [$mixed, 'Max']] as [$in, $nickname]) {
        $child = childOf($family, ['nickname' => $nickname, 'level' => 3]);
        CheckIn::factory()->create(['church_id' => $session->church_id, 'session_id' => $session->id, 'child_id' => $child->id, 'squad_id' => $in->id, 'checked_in_by' => null]);
    }

    expect(attendanceLines($session))->toBe(['Level 3: Max by ', 'Level 3 Boys: Bo by ', 'Level 3 Girls: Gia by ']);
});

it('carries each child\'s present mark, and never an undone child\'s — marked or not', function (): void {
    $satellite = satelliteOf(church());
    $family = familyAt($satellite);
    $session = sessionOf(scheduleAt($satellite));
    checkedIn($session, childOf($family, ['nickname' => 'Amara', 'level' => 2, 'gender' => GenderEnum::Girl]), ['present_at' => now()]);
    checkedIn($session, childOf($family, ['nickname' => 'Isabel', 'level' => 2, 'gender' => GenderEnum::Girl]));
    checkedIn($session, childOf($family, ['nickname' => 'Noah', 'level' => 2, 'gender' => GenderEnum::Girl]), ['present_at' => now(), 'undone_at' => now()]);

    expect(array_map(fn (CheckIn $c): array => [$c->child->nickname, $c->isPresent()], (new GetSessionAttendanceJob(session: $session))->handle()))
        ->toBe([['Amara', true], ['Isabel', false]]);
});
