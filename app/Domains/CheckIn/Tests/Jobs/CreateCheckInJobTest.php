<?php

declare(strict_types=1);

use App\Data\Models\CheckIn;
use App\Data\Models\Child;
use App\Data\Models\Satellite;
use App\Data\Models\Session;
use App\Data\Models\Squad;
use App\Data\Models\User;
use App\Domains\CheckIn\Jobs\CreateCheckInJob;
use App\Enums\CheckInResultEnum;
use Carbon\CarbonImmutable;

use function Pest\Laravel\travelTo;
use function Tests\Support\atTheSameTime;
use function Tests\Support\checkedIn;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;
use function Tests\Support\withCommittedWorld;

/*
 * One Check-in per child per Session (GLOSSARY "Check-in"): a second press says the child is already in, an Undo
 * then a new press revives the same row, and two phones pressing at the same moment still make one.
 */

/** @return array{session: Session, child: Child, squad: Squad, staff: User} */
function doorOf(): array
{
    $church = church();
    $dxb = Satellite::factory()->for($church)->withSquads()->create();
    $child = childOf(familyAt($dxb), ['level' => 2, 'gender' => 'girl']);

    return [
        'session' => sessionOf(scheduleAt($dxb)),
        'child' => $child,
        'squad' => Squad::query()->where('satellite_id', $dxb->id)->where('level', 2)->where('gender', 'girl')->sole(),
        'staff' => User::factory()->create(),
    ];
}

it('checks the child in to the Session, in the Squad, saying who and when', function (): void {
    ['session' => $session, 'child' => $child, 'squad' => $squad, 'staff' => $staff] = doorOf();
    travelTo(CarbonImmutable::parse('2026-10-31 09:05:00'));

    $result = (new CreateCheckInJob(session: $session, child: $child, squad: $squad, by: $staff))->handle();

    $in = CheckIn::query()->sole();
    expect($result)->toBe(CheckInResultEnum::CheckedIn)
        ->and([$in->church_id, $in->session_id, $in->child_id, $in->squad_id, $in->checked_in_by, $in->checked_in_at->toDateTimeString(), $in->isIn()])
        ->toBe([$session->church_id, $session->id, $child->id, $squad->id, $staff->id, '2026-10-31 09:05:00', true]);
});

it('says the child is already in on a second press, and makes no second Check-in', function (): void {
    ['session' => $session, 'child' => $child, 'squad' => $squad, 'staff' => $staff] = doorOf();
    $first = checkedIn($session, $child, ['checked_in_by' => $staff->id]);

    $result = (new CreateCheckInJob(session: $session, child: $child, squad: $squad, by: User::factory()->create()))->handle();

    expect($result)->toBe(CheckInResultEnum::AlreadyIn)
        ->and(CheckIn::query()->count())->toBe(1)
        ->and($first->fresh()?->checked_in_by)->toBe($staff->id);
});

it('checks the child in again after an Undo, on the same row, as a new revision', function (): void {
    ['session' => $session, 'child' => $child, 'squad' => $squad, 'staff' => $staff] = doorOf();
    $undone = checkedIn($session, $child, ['undone_at' => now()->subMinute(), 'undone_by' => $staff->id]);
    $again = User::factory()->create();

    $result = (new CreateCheckInJob(session: $session, child: $child, squad: $squad, by: $again))->handle();

    $row = $undone->fresh();
    expect($result)->toBe(CheckInResultEnum::CheckedIn)
        ->and(CheckIn::query()->count())->toBe(1)
        ->and([$row?->isIn(), $row?->undone_by, $row?->checked_in_by, $row?->revision])->toBe([true, null, $again->id, $undone->revision + 1]);
});

it('starts a child checked in again after an Undo unmarked — not present (roll call)', function (): void {
    ['session' => $session, 'child' => $child, 'squad' => $squad, 'staff' => $staff] = doorOf();
    // As if the Undo had missed the mark: the revive itself clears it.
    $undone = checkedIn($session, $child, ['undone_at' => now()->subMinute(), 'undone_by' => $staff->id, 'present_at' => now()->subMinutes(5), 'present_by' => $staff->id]);

    (new CreateCheckInJob(session: $session, child: $child, squad: $squad, by: $staff))->handle();

    expect([$undone->fresh()?->isIn(), $undone->fresh()?->isPresent(), $undone->fresh()?->present_by])->toBe([true, false, null]);
});

it('makes ONE Check-in when two phones check the same child in at the same moment', function (): void {
    ['session' => $session, 'child' => $child, 'squad' => $squad, 'staff' => $staff] = doorOf();
    $other = User::factory()->create();

    withCommittedWorld(function () use ($session, $child, $squad, $staff, $other): void {
        $results = atTheSameTime(
            fn (): CheckInResultEnum => (new CreateCheckInJob(session: $session, child: $child, squad: $squad, by: $staff))->handle(),
            fn (): CheckInResultEnum => (new CreateCheckInJob(session: $session, child: $child, squad: $squad, by: $other))->handle(),
        );

        $values = array_map(fn (mixed $r): string => $r instanceof CheckInResultEnum ? $r->value : 'not a result', $results);
        sort($values);
        expect($values)->toBe(['already_in', 'checked_in'])
            ->and(CheckIn::query()->where('child_id', $child->id)->count())->toBe(1);
    });
});
