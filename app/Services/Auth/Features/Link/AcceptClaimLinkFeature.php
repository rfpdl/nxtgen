<?php

declare(strict_types=1);

namespace App\Services\Auth\Features\Link;

use App\Domains\Auth\Jobs\SignInUserJob;
use App\Operations\Auth\AcceptFamilyClaimOperation;
use App\Services\Auth\Features\BaseAuthFeature;
use Illuminate\Http\RedirectResponse;

/** @extends BaseAuthFeature<RedirectResponse> */
final class AcceptClaimLinkFeature extends BaseAuthFeature
{
    public function __construct(private readonly string $token) {}

    public function handle(): RedirectResponse
    {
        $user = $this->run(new AcceptFamilyClaimOperation(token: $this->token));

        if ($user === null) {
            return redirect()->route('login')->with('error', self::LINK_FAILED);
        }

        $this->run(new SignInUserJob(user: $user));

        return redirect('/home');
    }
}
