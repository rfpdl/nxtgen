<?php

declare(strict_types=1);

use App\Domains\Auth\Jobs\SendNoAccountEmailJob;
use App\Notifications\NoAccountNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

/* Tells an address with no login that there is no account for it — sent to the address as normalised. */

it('emails the normalised address', function (): void {
    Notification::fake();

    (new SendNoAccountEmailJob(email: '  Nobody@Example.COM '))->handle();

    Notification::assertSentOnDemand(
        NoAccountNotification::class,
        fn (NoAccountNotification $n, array $channels, AnonymousNotifiable $to): bool => $to->routes['mail'] === 'nobody@example.com',
    );
});
