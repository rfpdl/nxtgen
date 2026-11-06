<?php

declare(strict_types=1);

namespace App\Services\Auth\Http\Controllers\Login;

use App\Services\Auth\Features\Login\ClearLoginFeature;
use App\Services\Auth\Features\Login\LogoutFeature;
use App\Services\Auth\Features\Login\PasswordLoginFeature;
use App\Services\Auth\Features\Login\RequestMagicLinkFeature;
use App\Services\Auth\Features\Login\ShowLoginFeature;
use App\Services\Auth\Features\Login\ShowPasswordLoginFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class Controller extends \Lucid\Units\Controller
{
    public function show(): Response
    {
        return $this->serve(ShowLoginFeature::class);
    }

    public function showPassword(): Response
    {
        return $this->serve(ShowPasswordLoginFeature::class);
    }

    public function clear(): RedirectResponse
    {
        return $this->serve(ClearLoginFeature::class);
    }

    public function requestLink(): RedirectResponse
    {
        return $this->serve(RequestMagicLinkFeature::class);
    }

    public function password(): RedirectResponse
    {
        return $this->serve(PasswordLoginFeature::class);
    }

    public function logout(): RedirectResponse
    {
        return $this->serve(LogoutFeature::class);
    }
}
