<?php

declare(strict_types=1);

namespace App\Services\Auth\Features\Login;

use App\Foundation\Attributes\UserStory;
use App\Services\Auth\Features\BaseAuthFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/** @extends BaseAuthFeature<RedirectResponse> */
#[UserStory(
    title: 'Sign out',
    persona: 'anyone signed in',
    happyPaths: ['story#23' => 'Signing out ends the session and goes back to the welcome page'],
)]
final class LogoutFeature extends BaseAuthFeature
{
    public function handle(): RedirectResponse
    {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    }
}
