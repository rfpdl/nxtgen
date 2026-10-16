<?php

declare(strict_types=1);

namespace App\Services\Auth\Features\Link;

use App\Domains\Auth\Jobs\SignInUserJob;
use App\Foundation\Attributes\UserStory;
use App\Operations\Auth\AcceptFamilyClaimOperation;
use App\Services\Auth\Features\BaseAuthFeature;
use Illuminate\Http\RedirectResponse;

/** @extends BaseAuthFeature<RedirectResponse> */
#[UserStory(
    title: 'Claim an imported family',
    persona: 'parent',
    happyPaths: ['story#14' => 'Accepting a claim links the Guardian to the Family, making the login if needed'],
    unhappyPaths: ['story#15' => 'A claim is refused when the login belongs to another Family or its email changed'],
)]
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
