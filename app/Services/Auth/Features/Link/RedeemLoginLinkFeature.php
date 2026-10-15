<?php

declare(strict_types=1);

namespace App\Services\Auth\Features\Link;

use App\Data\Models\User;
use App\Domains\Auth\Jobs\RedeemMagicLinkJob;
use App\Domains\Auth\Jobs\SignInUserJob;
use App\Domains\User\Jobs\GetUserForUpdateJob;
use App\Domains\User\Jobs\UpdateUserEmailVerifiedJob;
use App\Enums\MagicLinkPurposeEnum;
use App\Services\Auth\Features\BaseAuthFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Login and verify links both sign the person in; either proves they own the address. The user row is locked
 * for the redemption so an email correction cannot slip between consuming the link and confirming the address.
 *
 * @extends BaseAuthFeature<RedirectResponse>
 */
final class RedeemLoginLinkFeature extends BaseAuthFeature
{
    public function __construct(
        private readonly string $token,
        private readonly MagicLinkPurposeEnum $purpose,
    ) {}

    public function handle(): RedirectResponse
    {
        $user = DB::transaction(function (): ?User {
            $link = $this->run(new RedeemMagicLinkJob(token: $this->token, purpose: $this->purpose));
            if ($link === null || $link->user_id === null || $link->credential_version === null) {
                return null;
            }

            $user = $this->run(new GetUserForUpdateJob(userId: $link->user_id));
            $bound = $this->run(new UpdateUserEmailVerifiedJob(user: $user, boundEmail: $link->email_snapshot, boundVersion: $link->credential_version));

            return $bound ? $user : null;
        });

        if ($user === null) {
            return redirect()->route('login')->with('error', self::LINK_FAILED);
        }

        $this->run(new SignInUserJob(user: $user));

        return redirect('/home');
    }
}
