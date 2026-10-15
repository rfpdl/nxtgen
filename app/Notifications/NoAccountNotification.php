<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when someone asks for a sign-in link for an email we don't know. The login page gives the same
 * answer either way (no account oracle); only the owner of this inbox learns there is no account.
 */
final class NoAccountNotification extends Notification
{
    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('We could not find an account for this email')
            ->line('Someone asked for a sign-in link with this email, but no family or volunteer is registered with it.')
            ->line('If your family has not registered yet, ask a Lifeshaper at church for the registration link.')
            ->line('If you did not ask for this, you can ignore this email.');
    }
}
