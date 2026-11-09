<?php

declare(strict_types=1);

namespace App\Services\Platform\Http\Controllers\Settings;

use App\Services\Platform\Features\Settings\ShowSettingsFeature;
use App\Services\Platform\Features\Settings\UpdateSettingsFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class Controller extends \Lucid\Units\Controller
{
    public function show(): Response
    {
        return $this->serve(ShowSettingsFeature::class);
    }

    public function update(): RedirectResponse
    {
        return $this->serve(UpdateSettingsFeature::class);
    }
}
