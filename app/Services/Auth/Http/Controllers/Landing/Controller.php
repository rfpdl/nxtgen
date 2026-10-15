<?php

declare(strict_types=1);

namespace App\Services\Auth\Http\Controllers\Landing;

use App\Services\Auth\Features\Landing\ShowLandingFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class Controller extends \Lucid\Units\Controller
{
    public function show(): Response|RedirectResponse
    {
        return $this->serve(ShowLandingFeature::class);
    }
}
