<?php

declare(strict_types=1);

namespace App\Services\Church\Http\Controllers\Family;

use App\Services\Church\Features\Family\AddChildFeature;
use App\Services\Church\Features\Family\CorrectFamilyEmailFeature;
use App\Services\Church\Features\Family\ExportFamiliesFeature;
use App\Services\Church\Features\Family\RegisterFamilyFeature;
use App\Services\Church\Features\Family\ResendConfirmationFeature;
use App\Services\Church\Features\Family\ShowFamiliesFeature;
use App\Services\Church\Features\Family\ShowFamilyFeature;
use App\Services\Church\Features\Family\ShowRegisterFeature;
use App\Services\Church\Features\Family\UpdateFamilyFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class Controller extends \Lucid\Units\Controller
{
    public function index(): Response
    {
        return $this->serve(ShowFamiliesFeature::class);
    }

    public function export(): BinaryFileResponse
    {
        return $this->serve(ExportFamiliesFeature::class);
    }

    public function register(): Response
    {
        return $this->serve(ShowRegisterFeature::class);
    }

    public function store(): RedirectResponse
    {
        return $this->serve(RegisterFamilyFeature::class);
    }

    public function show(string $church, int $family): Response
    {
        return $this->serve(ShowFamilyFeature::class, ['familyId' => $family]);
    }

    public function update(string $church, int $family): RedirectResponse
    {
        return $this->serve(UpdateFamilyFeature::class, ['familyId' => $family]);
    }

    public function addChild(string $church, int $family): RedirectResponse
    {
        return $this->serve(AddChildFeature::class, ['familyId' => $family]);
    }

    public function resend(string $church, int $family): RedirectResponse
    {
        return $this->serve(ResendConfirmationFeature::class, ['familyId' => $family]);
    }

    public function correctEmail(string $church, int $family): RedirectResponse
    {
        return $this->serve(CorrectFamilyEmailFeature::class, ['familyId' => $family]);
    }
}
