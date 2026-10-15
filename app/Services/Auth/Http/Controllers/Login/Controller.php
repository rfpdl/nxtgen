<?php

declare(strict_types=1);

namespace App\Services\Auth\Http\Controllers\Login;

use App\Services\Auth\Features\Login\LogoutFeature;
use App\Services\Auth\Features\Login\RequestMagicLinkFeature;
use App\Services\Auth\Features\Login\ShowLoginFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class Controller extends \Lucid\Units\Controller
{
    public function show(): Response
    {
        return $this->serve(ShowLoginFeature::class);
    }

    public function requestLink(): RedirectResponse
    {
        return $this->serve(RequestMagicLinkFeature::class);
    }

    public function logout(): RedirectResponse
    {
        return $this->serve(LogoutFeature::class);
    }
}
