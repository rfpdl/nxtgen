<?php

declare(strict_types=1);

namespace App\Domains\Auth\Jobs;

use App\Domains\Auth\DataObjects\IssuedMagicLinkData;
use App\Enums\MagicLinkRouteEnum;
use App\Foundation\Jobs\BaseJob;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Returns false when the per-address limit stopped the email, so staff screens can say so honestly.
 *
 * @extends BaseJob<bool>
 */
final class SendMagicLinkJob extends BaseJob
{
    public function __construct(
        private readonly IssuedMagicLinkData $issued,
        private readonly string $churchName,
        private readonly bool $alreadyRegistered = false,
        private readonly ?string $invitedAs = null,
    ) {}

    public function handle(): bool
    {
        // One limit per RECIPIENT across every page that sends links (login, registration, resend), so no
        // endpoint can be used to flood an inbox (Codex diff review). Over the limit the email is quietly not
        // sent; the page still gives its usual answer.
        $key = 'magic-link-mail:'.hash('sha256', $this->issued->link->email_snapshot);
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return false;
        }
        RateLimiter::hit($key, 600);

        Notification::route('mail', $this->issued->link->email_snapshot)->notify(new MagicLinkNotification(
            url: route('auth.magic.confirm', ['purpose' => MagicLinkRouteEnum::fromPurpose($this->issued->link->purpose), 'token' => $this->issued->token]),
            purpose: $this->issued->link->purpose,
            churchName: $this->churchName,
            alreadyRegistered: $this->alreadyRegistered,
            invitedAs: $this->invitedAs,
        ));

        return true;
    }
}
