<?php

declare(strict_types=1);

use App\Data\Models\CheckIn;
use App\Data\Models\Satellite;
use App\Data\Models\Squad;
use App\Data\Models\User;
use App\Domains\CheckIn\Jobs\CreateCheckInJob;
use App\Domains\CheckIn\Jobs\UpdateCheckInAsUndoneJob;
use App\Domains\CheckIn\Jobs\UpdateCheckInPresenceJob;
use Carbon\CarbonImmutable;

use function Pest\Laravel\travelTo;
use function Tests\Support\checkedIn;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;

/*
 * Roll call (GLOSSARY "Roll call"): a tap marks a checked-in child present, with who and when, or unmarks them; latest
 * wins, no history. Only while the child is in — decided by the write itself, so an Undo at the same moment wins.
 */

function inForRollCall(): CheckIn
{
    $satellite = satelliteOf(church());

    return checkedIn(sessionOf(scheduleAt($satellite)), childOf(familyAt($satellite), ['level' => 2]));
}

it('marks a checked-in child present, saying who and when', function (): void {
    $in = inForRollCall();
    $leader = User::factory()->create();
    travelTo(CarbonImmutable::parse('2026-10-31 09:20:00'));

    $marked = (new UpdateCheckInPresenceJob(checkIn: $in, revision: 0, present: true, by: $leader))->handle();

    $row = $in->fresh();
    expect($marked)->toBeTrue()
        ->and([$row?->isPresent(), $row?->present_by, $row?->present_at?->toDateTimeString(), $row?->isIn()])
        ->toBe([true, $leader->id, '2026-10-31 09:20:00', true])
        ->and($in->isPresent())->toBeTrue();
});

it('keeps only the latest mark: marked again by someone else, then unmarked clears who and when', function (): void {
    $in = inForRollCall();
    $joy = User::factory()->create();
    $ben = User::factory()->create();

    travelTo(CarbonImmutable::parse('2026-10-31 09:20:00'));
    (new UpdateCheckInPresenceJob(checkIn: $in, revision: 0, present: true, by: $joy))->handle();
    travelTo(CarbonImmutable::parse('2026-10-31 10:05:00'));
    (new UpdateCheckInPresenceJob(checkIn: $in, revision: 0, present: true, by: $ben))->handle();
    expect([$in->fresh()?->present_by, $in->fresh()?->present_at?->toDateTimeString()])->toBe([$ben->id, '2026-10-31 10:05:00']);

    (new UpdateCheckInPresenceJob(checkIn: $in, revision: 0, present: false, by: $joy))->handle();
    expect([$in->fresh()?->isPresent(), $in->fresh()?->present_at, $in->fresh()?->present_by])->toBe([false, null, null]);
});

it('marks nothing, and says so, when the Check-in was undone after it was read', function (): void {
    $in = inForRollCall();
    // The door's Undo lands between this request reading the Check-in and writing the mark.
    CheckIn::query()->whereKey($in->id)->update(['undone_at' => now()]);

    $marked = (new UpdateCheckInPresenceJob(checkIn: $in, revision: 0, present: true, by: User::factory()->create()))->handle();

    expect($marked)->toBeFalse()
        ->and($in->fresh()?->isPresent())->toBeFalse();
});

it('refuses a tap made before an Undo and a re-check-in — the revived Check-in is a new revision (Astra)', function (): void {
    $satellite = Satellite::factory()->for(church())->withSquads()->create();
    $session = sessionOf(scheduleAt($satellite));
    $child = childOf(familyAt($satellite), ['level' => 2, 'gender' => 'girl']);
    $squad = Squad::query()->where('satellite_id', $satellite->id)->where('level', 2)->where('gender', 'girl')->sole();
    $staff = User::factory()->create();
    $in = checkedIn($session, $child, ['squad_id' => $squad->id]);
    $tappedOn = $in->revision;
    // The tap is on its way; meanwhile the door undoes the Check-in and checks the child in again (the same row).
    (new UpdateCheckInAsUndoneJob(checkIn: $in, by: $staff))->handle();
    (new CreateCheckInJob(session: $session, child: $child, squad: $squad, by: $staff))->handle();

    $marked = (new UpdateCheckInPresenceJob(checkIn: $in, revision: $tappedOn, present: true, by: $staff))->handle();

    $row = $in->fresh();
    expect($marked)->toBeFalse()
        ->and([$row?->isIn(), $row?->isPresent(), $row?->revision])->toBe([true, false, $tappedOn + 1]);
});
