<?php

declare(strict_types=1);

use App\Domains\Church\Jobs\UpdateChurchSettingsJob;

use function Tests\Support\church;

/* The church's settings, saved together (spec item 12). */

it('saves the time zone, the card fee and the Moving-Up date', function (): void {
    $church = church(['timezone' => 'Asia/Dubai']);

    (new UpdateChurchSettingsJob(church: $church, timezone: 'Asia/Manila', cardFeeMinor: 1550, movingUpDate: '2027-03-01'))->handle();

    $church->refresh();
    expect([$church->timezone, $church->card_fee_minor, $church->moving_up_reference_date?->toDateString()])->toBe(['Asia/Manila', 1550, '2027-03-01']);
});
