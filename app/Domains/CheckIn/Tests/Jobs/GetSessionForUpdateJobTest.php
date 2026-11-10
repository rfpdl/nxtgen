<?php

declare(strict_types=1);

use App\Data\Models\Session;
use App\Domains\CheckIn\Jobs\GetSessionForUpdateJob;
use Illuminate\Support\Facades\DB;

use function Tests\Support\church;
use function Tests\Support\satelliteOf;
use function Tests\Support\scheduleAt;
use function Tests\Support\sessionOf;

/* The Session as it is now — not as the page loaded it — so a Check-in sees a close made meanwhile (spec item 15). */

it('reads the Session as it is now, inside a transaction', function (): void {
    $loaded = sessionOf(scheduleAt(satelliteOf(church())));
    Session::query()->whereKey($loaded->id)->update(['closed_at' => now()]);   // another admin closed it meanwhile

    $now = DB::transaction(fn (): Session => (new GetSessionForUpdateJob(session: $loaded))->handle());

    expect($loaded->closed_at)->toBeNull()
        ->and($now->closed_at)->not->toBeNull()
        ->and($now->id)->toBe($loaded->id);
});
