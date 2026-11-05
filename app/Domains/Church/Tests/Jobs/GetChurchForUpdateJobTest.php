<?php

declare(strict_types=1);

use App\Domains\Church\Jobs\GetChurchForUpdateJob;

use function Tests\Support\church;

/* The church's own row, locked for the caller's transaction: one Moving-Up (or put-back) at a time per church. */

it('reads the church again', function (): void {
    $church = church();

    expect((new GetChurchForUpdateJob(church: $church))->handle()->id)->toBe($church->id);
});
