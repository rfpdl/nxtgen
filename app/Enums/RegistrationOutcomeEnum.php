<?php

declare(strict_types=1);

namespace App\Enums;

enum RegistrationOutcomeEnum: string
{
    case Registered = 'registered';
    case AlreadyRegistered = 'already_registered';
    case ClaimSent = 'claim_sent';
    case ClaimResent = 'claim_resent';

    public function label(): string
    {
        return match ($this) {
            self::Registered => 'Registered',
            self::AlreadyRegistered => 'Already registered',
            self::ClaimSent => 'Claim sent',
            self::ClaimResent => 'Claim re-sent',
        };
    }

    /**
     * What the PUBLIC form says — the same words for every outcome, so the page never reveals whether an email
     * already belongs to an account or to a family in this church (Codex diff review: membership oracle). The
     * specific next step (confirm, sign in, connect) arrives only in the email, to the inbox owner.
     */
    public function parentMessage(): string
    {
        return 'Thank you! Please check your email to finish. If nothing arrives in a few minutes, check your spam folder or ask a Lifeshaper at church.';
    }
}
