<?php

declare(strict_types=1);

use App\Domains\CheckIn\Jobs\UpdateSessionAsOpenJob;
use Carbon\CarbonImmutable;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;

/* Reopening a closed Session the same day (spec item 15): it is open again, and no longer says who closed it. */

it('opens a closed Session again', function (): void {
    $session = sessionOf(scheduleAt(satelliteOf(church())));
    $session->update(['closed_at' => now()]);

    (new UpdateSessionAsOpenJob(session: $session))->handle();

    expect([$session->fresh()?->closed_at, $session->fresh()?->closed_by])->toBe([null, null])
        ->and($session->fresh()?->isOpenOn(CarbonImmutable::parse('2026-10-31')))->toBeTrue();
});
