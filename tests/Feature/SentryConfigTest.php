<?php

declare(strict_types=1);

use App\Foundation\Support\SentryScrubber;

/* Pins the two settings that keep personal data out of Sentry (plan §1); the scrubber itself is unit-tested. */

it('never sends personal data by default, and scrubs every event', function (): void {
    expect(config('sentry.send_default_pii'))->toBeFalse()
        ->and(config('sentry.before_send'))->toBe([SentryScrubber::class, 'beforeSend'])
        ->and(is_callable(config('sentry.before_send')))->toBeTrue();
});

it('is off until a DSN is set', function (): void {
    expect(config('sentry.dsn'))->toBeNull();
});
