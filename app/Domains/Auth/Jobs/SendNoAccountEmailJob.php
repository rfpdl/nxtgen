<?php

declare(strict_types=1);

namespace App\Domains\Auth\Jobs;

use App\Foundation\Jobs\BaseJob;
use App\Notifications\NoAccountNotification;
use Illuminate\Support\Facades\Notification;

/** @extends BaseJob<void> */
final class SendNoAccountEmailJob extends BaseJob
{
    public function __construct(private readonly string $email) {}

    public function handle(): void
    {
        Notification::route('mail', mb_strtolower(trim($this->email)))->notify(new NoAccountNotification);
    }
}
