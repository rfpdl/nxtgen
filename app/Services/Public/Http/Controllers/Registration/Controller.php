<?php

declare(strict_types=1);

namespace App\Services\Public\Http\Controllers\Registration;

use App\Data\Models\Church;
use App\Services\Public\Features\Registration\RegisterFamilyFeature;
use App\Services\Public\Features\Registration\ShowRegisterFeature;
use App\Services\Public\Features\Registration\ShowRegisterThanksFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class Controller extends \Lucid\Units\Controller
{
    public function show(Church $church): Response
    {
        return $this->serve(ShowRegisterFeature::class, ['church' => $church]);
    }

    public function store(Church $church): RedirectResponse
    {
        return $this->serve(RegisterFamilyFeature::class, ['church' => $church]);
    }

    public function thanks(Church $church): Response
    {
        return $this->serve(ShowRegisterThanksFeature::class, ['church' => $church]);
    }
}
