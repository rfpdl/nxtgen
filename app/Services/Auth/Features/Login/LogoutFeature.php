<?php

declare(strict_types=1);

namespace App\Services\Auth\Features\Login;

use App\Services\Auth\Features\BaseAuthFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/** @extends BaseAuthFeature<RedirectResponse> */
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
