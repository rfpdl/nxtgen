<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\CheckIn\Jobs\UpdateSessionAsClosedJob;
use Carbon\CarbonImmutable;

use function Pest\Laravel\travelTo;
use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;

/* An admin closes a Session (spec item 15): it is closed from then on, saying when and by whom. */

it('closes the Session, saying when and by whom', function (): void {
    travelTo(CarbonImmutable::parse('2026-10-31 11:15:00'));
    $session = sessionOf(scheduleAt(satelliteOf(church())));
    $admin = User::factory()->create();

    (new UpdateSessionAsClosedJob(session: $session, by: $admin))->handle();

    $closed = $session->fresh();
    expect([$closed?->closed_at?->toDateTimeString(), $closed?->closed_by])->toBe(['2026-10-31 11:15:00', $admin->id])
        ->and($closed?->isOpenOn(CarbonImmutable::parse('2026-10-31')))->toBeFalse();
});

it('leaves the other Sessions open', function (): void {
    $satellite = satelliteOf(church());
    $session = sessionOf(scheduleAt($satellite, ['name' => '1PM']));
    $other = sessionOf(scheduleAt($satellite, ['name' => '7PM', 'starts_at' => '19:00']));

    (new UpdateSessionAsClosedJob(session: $session, by: null))->handle();

    expect($other->fresh()?->closed_at)->toBeNull();
});
