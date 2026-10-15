<?php

declare(strict_types=1);

namespace App\Services\Auth\Features\Login;

use App\Services\Auth\Features\BaseAuthFeature;
use Inertia\Inertia;
use Inertia\Response;

/** @extends BaseAuthFeature<Response> */
final class ShowLoginFeature extends BaseAuthFeature
{
    public function handle(): Response
    {
        return Inertia::render('Auth/Login');
    }
}
