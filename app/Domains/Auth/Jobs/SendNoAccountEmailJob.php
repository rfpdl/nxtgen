<?php

declare(strict_types=1);

namespace App\Domains\Auth\Jobs;

use App\Foundation\Jobs\BaseJob;
use App\Foundation\Support\EmailAddress;
use App\Notifications\NoAccountNotification;
use Illuminate\Support\Facades\Notification;

/** @extends BaseJob<void> */
final class SendNoAccountEmailJob extends BaseJob
{
    public function __construct(private readonly string $email) {}

    public function handle(): void
    {
        Notification::route('mail', EmailAddress::normalise($this->email))->notify(new NoAccountNotification);
    }
}
