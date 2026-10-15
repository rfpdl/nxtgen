<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\MagicLinkPurposeEnum;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** The email carrying a magic link. Plain words: parents read this on a phone. */
final class MagicLinkNotification extends Notification
{
    public function __construct(
        public readonly string $url,
        public readonly MagicLinkPurposeEnum $purpose,
        public readonly string $churchName,
        public readonly bool $alreadyRegistered = false,
        public readonly ?string $invitedAs = null,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->purpose === MagicLinkPurposeEnum::Login && $this->invitedAs !== null) {
            return (new MailMessage)
                ->subject("You have been added at {$this->churchName}")
                ->line("You were added as {$this->invitedAs} at {$this->churchName}.")
                ->line('Tap the button to sign in. The link works once and expires in 2 hours. After that, sign in any time from the sign-in page with this email.')
                ->action('Sign in', $this->url);
        }

        return match ($this->purpose) {
            MagicLinkPurposeEnum::Login => (new MailMessage)
                ->subject("Your sign-in link for {$this->churchName}")
                ->lineIf($this->alreadyRegistered, 'Someone tried to register a family with this email, but your family is already registered with us. To add a child, sign in and use your family page.')
                ->line('Tap the button to sign in. The link works once and expires in 2 hours.')
                ->action('Sign in', $this->url)
                ->line('If you did not ask for this, you can ignore this email.'),
            MagicLinkPurposeEnum::VerifyEmail => (new MailMessage)
                ->subject("Please confirm your email for {$this->churchName}")
                ->line('Your family is registered. Tap the button to confirm this is your email and see your family page.')
                ->action('Confirm my email', $this->url)
                ->line('The link expires in 7 days. A volunteer can send a new one any time.'),
            MagicLinkPurposeEnum::ClaimFamily => (new MailMessage)
                ->subject("Finish registering your family at {$this->churchName}")
                ->line('A family was registered with this email. Tap the button to connect it to your account.')
                ->action('Connect my family', $this->url)
                ->line('If this was not you, ignore this email and nothing will be connected.'),
        };
    }
}
