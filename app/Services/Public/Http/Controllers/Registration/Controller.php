<?php

declare(strict_types=1);

namespace App\Services\Public\Http\Controllers\Registration;

use App\Data\Models\Organization;
use App\Services\Public\Features\Registration\RegisterFamilyFeature;
use App\Services\Public\Features\Registration\ShowRegisterFeature;
use App\Services\Public\Features\Registration\ShowRegisterThanksFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class Controller extends \Lucid\Units\Controller
{
    public function show(Organization $organization): Response
    {
        return $this->serve(ShowRegisterFeature::class, ['organization' => $organization]);
    }

    public function store(Organization $organization): RedirectResponse
    {
        return $this->serve(RegisterFamilyFeature::class, ['organization' => $organization]);
    }

    public function thanks(Organization $organization): Response
    {
        return $this->serve(ShowRegisterThanksFeature::class, ['organization' => $organization]);
    }
}
