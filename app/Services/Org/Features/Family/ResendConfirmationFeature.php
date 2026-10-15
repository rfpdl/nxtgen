<?php

declare(strict_types=1);

namespace App\Services\Org\Features\Family;

use App\Domains\Auth\Jobs\CreateMagicLinkJob;
use App\Domains\Auth\Jobs\SendMagicLinkJob;
use App\Domains\Family\Jobs\GetFamilyByIdJob;
use App\Domains\Family\Jobs\GetPendingClaimForFamilyJob;
use App\Enums\AccessRowEnum;
use App\Enums\MagicLinkPurposeEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Support\EmailMask;
use App\Services\Org\Features\BaseOrgFeature;
use Illuminate\Http\RedirectResponse;

/**
 * "Email not confirmed" → send the link again (plan §7.2). A pending claim is re-sent as a claim link; an
 * unconfirmed login gets a fresh confirm link. Who may: AccessScope::canResendLink (plan §3.2).
 *
 * @extends BaseOrgFeature<RedirectResponse>
 */
#[AccessRow(AccessRowEnum::ResendLink)]
final class ResendConfirmationFeature extends BaseOrgFeature
{
    public function __construct(private readonly int $familyId) {}

    public function handle(): RedirectResponse
    {
        $org = $this->organization();
        $family = $this->run(new GetFamilyByIdJob(organizationId: $org->id, id: $this->familyId));
        abort_unless($this->scope()->canResendLink($family), 404);
        $user = $family->primaryGuardian?->user;

        $claim = $this->run(new GetPendingClaimForFamilyJob(family: $family));
        if ($claim !== null) {
            $issued = $this->run(new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::ClaimFamily, email: $claim->email_normalised, claim: $claim));
        } elseif ($user !== null && $user->email_verified_at === null) {
            $issued = $this->run(new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::VerifyEmail, email: $user->email, user: $user));
        } else {
            return back()->with('success', 'This email is already confirmed.');
        }

        if (! $this->run(new SendMagicLinkJob(issued: $issued, churchName: $org->name))) {
            return back()->with('error', 'Too many emails were sent to this address recently. Please try again in 10 minutes.');
        }

        return back()->with('success', 'We sent the confirmation link to '.EmailMask::mask($issued->link->email_snapshot).' again.');
    }
}
