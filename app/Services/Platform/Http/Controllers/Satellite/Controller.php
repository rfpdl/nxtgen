<?php

declare(strict_types=1);

namespace App\Services\Platform\Http\Controllers\Satellite;

use App\Data\Models\Satellite;
use App\Services\Platform\Features\Satellite\AddSatelliteFeature;
use App\Services\Platform\Features\Satellite\ShowSatellitesFeature;
use App\Services\Platform\Features\Satellite\UpdateSatelliteFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class Controller extends \Lucid\Units\Controller
{
    public function index(): Response
    {
        return $this->serve(ShowSatellitesFeature::class);
    }

    public function store(): RedirectResponse
    {
        return $this->serve(AddSatelliteFeature::class);
    }

    public function update(Satellite $satellite): RedirectResponse
    {
        return $this->serve(UpdateSatelliteFeature::class, ['satellite' => $satellite]);
    }
}
