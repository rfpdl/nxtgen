<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Family;

use App\Domains\Auth\Jobs\CreateMagicLinkJob;
use App\Domains\Auth\Jobs\SendMagicLinkJob;
use App\Domains\Family\Jobs\UpdateFamilyEmailJob;
use App\Domains\User\Jobs\GetUserForUpdateJob;
use App\Domains\User\Jobs\GetUserIsFamilyOnlyJob;
use App\Enums\AccessRowEnum;
use App\Enums\MagicLinkPurposeEnum;
use App\Foundation\Attributes\AccessRow;
use App\Services\Church\Data\CorrectFamilyEmailInput;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Fix a mistyped family email (plan §4.4, v1). Allowed only when the login is used by this family alone, is
 * not confirmed and is not staff — otherwise a satellite admin could take over a staff account. Anything else
 * is refused with a plain message (security list #1 tracks the full flow).
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[AccessRow(AccessRowEnum::CorrectContact)]
final class CorrectFamilyEmailFeature extends BaseChurchFeature
{
    public function __construct(private readonly int $familyId) {}

    public function handle(CorrectFamilyEmailInput $input): RedirectResponse
    {
        $church = $this->church();
        $family = $this->scopedFamily($this->familyId, $this->scope()->canEditFamily(...));

        $login = $family->primaryGuardian?->user;

        // Lock the login and re-check eligibility UNDER the lock, so a link redemption cannot interleave.
        $issued = $login === null ? null : DB::transaction(function () use ($login, $input, $family) {
            $user = $this->run(new GetUserForUpdateJob(user: $login));
            if (! $this->run(new GetUserIsFamilyOnlyJob(user: $user))) {
                return null;
            }
            $this->run(new UpdateFamilyEmailJob(family: $family, login: $user, email: $input->email, by: $this->staff()));

            return $this->run(new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::VerifyEmail, email: $user->email, user: $user));
        });

        if ($issued === null) {
            return back()->with('error', 'This email cannot be changed here: it is already confirmed or is also a volunteer login. Ask a church admin.');
        }

        if (! $this->run(new SendMagicLinkJob(issued: $issued, churchName: $church->name))) {
            return back()->with('error', 'Email updated, but too many emails went to this address recently, so no link was sent. Use "Send the confirmation link again" in 10 minutes.');
        }

        return back()->with('success', 'Email updated. A new confirmation link is on its way.');
    }
}
