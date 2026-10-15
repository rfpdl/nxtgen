<?php

declare(strict_types=1);

namespace App\Services\Family\Http\Controllers\Home;

use App\Services\Family\Features\Home\ShowFamilyFeature;
use App\Services\Family\Features\Home\UpdateMyFamilyFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class Controller extends \Lucid\Units\Controller
{
    public function show(): Response
    {
        return $this->serve(ShowFamilyFeature::class);
    }

    public function update(): RedirectResponse
    {
        return $this->serve(UpdateMyFamilyFeature::class);
    }
}
