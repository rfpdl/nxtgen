<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Family;

use App\Domains\Auth\Jobs\CreateMagicLinkJob;
use App\Domains\Auth\Jobs\SendMagicLinkJob;
use App\Domains\Family\Jobs\GetPendingClaimForFamilyJob;
use App\Enums\AccessRowEnum;
use App\Enums\MagicLinkPurposeEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Support\EmailMask;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;

/**
 * "Email not confirmed" → send the link again (plan §7.2). A pending claim is re-sent as a claim link; an
 * unconfirmed login gets a fresh confirm link. Who may: AccessScope::canResendLink (plan §3.2).
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[AccessRow(AccessRowEnum::ResendLink)]
final class ResendConfirmationFeature extends BaseChurchFeature
{
    public function __construct(private readonly int $familyId) {}

    public function handle(): RedirectResponse
    {
        $church = $this->church();
        $family = $this->scopedFamily($this->familyId, $this->scope()->canResendLink(...));
        $user = $family->primaryGuardian?->user;

        $claim = $this->run(new GetPendingClaimForFamilyJob(family: $family));
        if ($claim !== null) {
            $issued = $this->run(new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::ClaimFamily, email: $claim->email_normalised, claim: $claim));
        } elseif ($user !== null && $user->email_verified_at === null) {
            $issued = $this->run(new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::VerifyEmail, email: $user->email, user: $user));
        } else {
            return back()->with('success', 'This email is already confirmed.');
        }

        if (! $this->run(new SendMagicLinkJob(issued: $issued, churchName: $church->name))) {
            return back()->with('error', 'Too many emails were sent to this address recently. Please try again in 10 minutes.');
        }

        return back()->with('success', 'We sent the confirmation link to '.EmailMask::mask($issued->link->email_snapshot).' again.');
    }
}
