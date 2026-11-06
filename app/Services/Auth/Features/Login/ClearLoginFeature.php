<?php

declare(strict_types=1);

namespace App\Services\Auth\Features\Login;

use App\Foundation\Attributes\UserStory;
use App\Services\Auth\Features\BaseAuthFeature;
use App\Services\Auth\Features\Landing\ShowLandingFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

/**
 * /loginc (Roy, 2026-11-06): clear this device's sign-in in one address — signed out (the remember-me cookie too),
 * the remembered landing page forgotten — then back to sign in. A GET, so typing it is enough; the worst a link to
 * it can do is sign someone out (SECURITY.md).
 *
 * @extends BaseAuthFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Clear the sign-in on this device',
    persona: 'anyone',
    happyPaths: ['story#163' => 'One address signs out and forgets the remembered landing page'],
)]
final class ClearLoginFeature extends BaseAuthFeature
{
    public function handle(): RedirectResponse
    {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        Cookie::queue(Cookie::forget(ShowLandingFeature::REMEMBERED));

        return redirect('/login')->with('success', 'Signed out. This device is cleared.');
    }
}
