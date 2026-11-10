<?php

declare(strict_types=1);

use App\Data\Models\User;
use App\Domains\CheckIn\Jobs\UpdateSessionAsOpenJob;
use Carbon\CarbonImmutable;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;

/* Reopening a closed Session the same day (spec item 15): it is open again, no longer says who closed it, and keeps its code. */

it('opens a closed Session again', function (): void {
    $session = sessionOf(scheduleAt(satelliteOf(church())));
    $session->update(['closed_at' => now(), 'closed_by' => User::factory()->create()->id]);
    $code = $session->code;

    (new UpdateSessionAsOpenJob(session: $session))->handle();

    // The same code (Roy 2026-11-10), so a link already shared still works.
    expect([$session->fresh()?->closed_at, $session->fresh()?->closed_by, $session->fresh()?->code])->toBe([null, null, $code])
        ->and($session->fresh()?->isOpenOn(CarbonImmutable::parse('2026-10-31')))->toBeTrue();
});
