<?php

declare(strict_types=1);

use App\Data\Models\CheckIn;
use App\Domains\CheckIn\Jobs\GetFamilyCheckInsJob;
use App\Enums\GenderEnum;

use function Tests\Support\checkedIn;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;

/* A family's children's attendance, for the staff family page: each Check-in not undone, newest first, with present. */

it('lists the family\'s children\'s Check-ins, newest Session first — never an undone one, nor another family\'s', function (): void {
    $satellite = satelliteOf(church(), ['name' => 'CCF DXB']);
    $onePm = scheduleAt($satellite, ['name' => '1PM', 'starts_at' => '13:00']);
    $sevenPm = scheduleAt($satellite, ['name' => '7PM', 'starts_at' => '19:00']);
    $family = familyAt($satellite);
    $amara = childOf($family, ['nickname' => 'Amara', 'level' => 2, 'gender' => GenderEnum::Girl]);
    $ben = childOf($family, ['nickname' => 'Ben', 'level' => 1, 'gender' => GenderEnum::Boy]);
    checkedIn(sessionOf($onePm, '2026-10-17'), $amara);
    checkedIn(sessionOf($onePm, '2026-10-31'), $amara, ['present_at' => now()]);
    checkedIn(sessionOf($sevenPm, '2026-10-31'), $ben);
    checkedIn(sessionOf($onePm, '2026-10-24'), $amara, ['undone_at' => now(), 'present_at' => now()]);
    checkedIn(sessionOf($onePm, '2026-10-10'), childOf(familyAt($satellite), ['level' => 1]));

    $nicknames = [$amara->id => 'Amara', $ben->id => 'Ben'];
    $lines = array_map(
        fn (CheckIn $c): string => "{$nicknames[$c->child_id]} {$c->session->date->toDateString()} {$c->session->schedule->name} {$c->session->satellite->name} {$c->squad->name()} ".($c->isPresent() ? 'present' : 'not marked'),
        (new GetFamilyCheckInsJob(family: $family))->handle(),
    );

    expect($lines)->toBe([
        'Ben 2026-10-31 7PM CCF DXB Level 1 not marked',
        'Amara 2026-10-31 1PM CCF DXB Level 2 Girls present',
        'Amara 2026-10-17 1PM CCF DXB Level 2 Girls not marked',
    ]);
});

it('has nothing for a family never checked in', function (): void {
    expect((new GetFamilyCheckInsJob(family: familyAt(satelliteOf(church()))))->handle())->toBe([]);
});

it('leaves out a Check-in of the child carrying another church — the church is named in the query', function (): void {
    $satellite = satelliteOf(church());
    $family = familyAt($satellite);
    $amara = childOf($family, ['level' => 2, 'gender' => GenderEnum::Girl]);
    $theirs = sessionOf(scheduleAt(satelliteOf(church())));
    CheckIn::factory()->create(['church_id' => $theirs->church_id, 'session_id' => $theirs->id, 'child_id' => $amara->id]);

    expect((new GetFamilyCheckInsJob(family: $family))->handle())->toBe([]);
});
