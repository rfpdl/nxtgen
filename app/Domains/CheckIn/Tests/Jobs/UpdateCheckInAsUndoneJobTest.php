<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\CheckIn\Jobs\UpdateCheckInAsUndoneJob;
use Carbon\CarbonImmutable;

use function Pest\Laravel\travelTo;
use function Tests\Support\checkedIn;
use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;

/* An Undo keeps the row and says who undid it and when; the child is no longer in, nor marked present. */

it('undoes a Check-in, saying who and when, and keeps the row', function (): void {
    $satellite = satelliteOf(church());
    $in = checkedIn(sessionOf(scheduleAt($satellite)), childOf(familyAt($satellite), ['level' => 1]));
    $staff = User::factory()->create();
    travelTo(CarbonImmutable::parse('2026-10-31 09:07:00'));

    (new UpdateCheckInAsUndoneJob(checkIn: $in, by: $staff))->handle();

    $row = $in->fresh();
    expect([$row?->isIn(), $row?->undone_by, $row?->undone_at?->toDateTimeString()])->toBe([false, $staff->id, '2026-10-31 09:07:00']);
});

it('clears the child\'s present mark (roll call)', function (): void {
    $satellite = satelliteOf(church());
    $staff = User::factory()->create();
    $in = checkedIn(sessionOf(scheduleAt($satellite)), childOf(familyAt($satellite), ['level' => 1]), ['present_at' => now(), 'present_by' => $staff->id]);

    (new UpdateCheckInAsUndoneJob(checkIn: $in, by: $staff))->handle();

    expect([$in->fresh()?->isPresent(), $in->fresh()?->present_by])->toBe([false, null]);
});
