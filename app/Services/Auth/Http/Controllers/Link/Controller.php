<?php

declare(strict_types=1);

namespace App\Services\Auth\Http\Controllers\Link;

use App\Enums\MagicLinkPurposeEnum;
use App\Services\Auth\Features\Link\AcceptClaimLinkFeature;
use App\Services\Auth\Features\Link\RedeemLoginLinkFeature;
use App\Services\Auth\Features\Link\ShowConfirmLinkFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class Controller extends \Lucid\Units\Controller
{
    public function login(string $token): RedirectResponse
    {
        return $this->serve(RedeemLoginLinkFeature::class, ['token' => $token, 'purpose' => MagicLinkPurposeEnum::Login]);
    }

    public function verify(string $token): RedirectResponse
    {
        return $this->serve(RedeemLoginLinkFeature::class, ['token' => $token, 'purpose' => MagicLinkPurposeEnum::VerifyEmail]);
    }

    public function claim(string $token): RedirectResponse
    {
        return $this->serve(AcceptClaimLinkFeature::class, ['token' => $token]);
    }

    public function confirm(string $purpose, string $token): Response
    {
        return $this->serve(ShowConfirmLinkFeature::class, ['purpose' => $purpose, 'token' => $token]);
    }
}
