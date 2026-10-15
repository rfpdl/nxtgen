<?php

declare(strict_types=1);

namespace App\Services\Org\Http\Controllers\Family;

use App\Services\Org\Features\Family\AddChildFeature;
use App\Services\Org\Features\Family\CorrectFamilyEmailFeature;
use App\Services\Org\Features\Family\ExportFamiliesFeature;
use App\Services\Org\Features\Family\RegisterFamilyFeature;
use App\Services\Org\Features\Family\ResendConfirmationFeature;
use App\Services\Org\Features\Family\ShowFamiliesFeature;
use App\Services\Org\Features\Family\ShowFamilyFeature;
use App\Services\Org\Features\Family\ShowRegisterFeature;
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

    public function show(string $organization, int $family): Response
    {
        return $this->serve(ShowFamilyFeature::class, ['familyId' => $family]);
    }

    public function addChild(string $organization, int $family): RedirectResponse
    {
        return $this->serve(AddChildFeature::class, ['familyId' => $family]);
    }

    public function resend(string $organization, int $family): RedirectResponse
    {
        return $this->serve(ResendConfirmationFeature::class, ['familyId' => $family]);
    }

    public function correctEmail(string $organization, int $family): RedirectResponse
    {
        return $this->serve(CorrectFamilyEmailFeature::class, ['familyId' => $family]);
    }
}
