<?php

declare(strict_types=1);

namespace App\Services\Org\Features\Family;

use App\Domains\Auth\Jobs\CreateMagicLinkJob;
use App\Domains\Auth\Jobs\SendMagicLinkJob;
use App\Domains\Family\Jobs\CreateSystemNoteJob;
use App\Domains\Family\Jobs\GetFamilyByIdJob;
use App\Domains\User\Jobs\GetUserForUpdateJob;
use App\Domains\User\Jobs\GetUserIsFamilyOnlyJob;
use App\Domains\User\Jobs\UpdateUserEmailJob;
use App\Enums\AccessRowEnum;
use App\Enums\MagicLinkPurposeEnum;
use App\Foundation\Attributes\AccessRow;
use App\Services\Org\Data\CorrectFamilyEmailInput;
use App\Services\Org\Features\BaseOrgFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Fix a mistyped family email (plan §4.4, v1). Allowed only when the login is used by this family alone, is
 * not confirmed and is not staff — otherwise a satellite admin could take over a staff account. Anything else
 * is refused with a plain message (security list #1 tracks the full flow).
 *
 * @extends BaseOrgFeature<RedirectResponse>
 */
#[AccessRow(AccessRowEnum::CorrectContact)]
final class CorrectFamilyEmailFeature extends BaseOrgFeature
{
    public function __construct(private readonly int $familyId) {}

    public function handle(CorrectFamilyEmailInput $input): RedirectResponse
    {
        $org = $this->organization();
        $family = $this->run(new GetFamilyByIdJob(organizationId: $org->id, id: $this->familyId));
        abort_unless($this->scope()->canEditFamily($family), 404);

        $userId = $family->primaryGuardian?->user_id;

        // Lock the login and re-check eligibility UNDER the lock, so a link redemption cannot interleave.
        $issued = $userId === null ? null : DB::transaction(function () use ($userId, $input, $family) {
            $user = $this->run(new GetUserForUpdateJob(userId: $userId));
            if (! $this->run(new GetUserIsFamilyOnlyJob(user: $user))) {
                return null;
            }
            $from = $user->email;
            $this->run(new UpdateUserEmailJob(user: $user, email: $input->email));
            $this->run(new CreateSystemNoteJob(about: $family, body: "Email corrected from {$from} to {$user->email}.", by: $this->staff()));

            return $this->run(new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::VerifyEmail, email: $user->email, user: $user));
        });

        if ($issued === null) {
            return back()->with('error', 'This email cannot be changed here: it is already confirmed or is also a volunteer login. Ask a church admin.');
        }

        if (! $this->run(new SendMagicLinkJob(issued: $issued, churchName: $org->name))) {
            return back()->with('error', 'Email updated, but too many emails went to this address recently, so no link was sent. Use "Send the confirmation link again" in 10 minutes.');
        }

        return back()->with('success', 'Email updated. A new confirmation link is on its way.');
    }
}
