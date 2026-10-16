<?php

declare(strict_types=1);

namespace App\Services\Church\Http\Controllers\Child;

use App\Services\Church\Features\Child\RecordPhotoConsentFeature;
use App\Services\Church\Features\Child\ShowChildrenFeature;
use App\Services\Church\Features\Child\StaffAddChildPhotoFeature;
use App\Services\Church\Features\Child\UpdateChildDetailsFeature;
use App\Services\Church\Features\Child\UpdateChildGroupFeature;
use App\Services\Church\Features\Child\UpdateChildStatusFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class Controller extends \Lucid\Units\Controller
{
    public function index(): Response
    {
        return $this->serve(ShowChildrenFeature::class);
    }

    public function updateGroup(string $church, int $child): RedirectResponse
    {
        return $this->serve(UpdateChildGroupFeature::class, ['childId' => $child]);
    }

    public function update(string $church, int $child): RedirectResponse
    {
        return $this->serve(UpdateChildDetailsFeature::class, ['childId' => $child]);
    }

    public function updateStatus(string $church, int $child): RedirectResponse
    {
        return $this->serve(UpdateChildStatusFeature::class, ['childId' => $child]);
    }

    public function addPhoto(string $church, int $child): RedirectResponse
    {
        return $this->serve(StaffAddChildPhotoFeature::class, ['childId' => $child]);
    }

    public function recordPhotoConsent(string $church, int $child): RedirectResponse
    {
        return $this->serve(RecordPhotoConsentFeature::class, ['childId' => $child]);
    }
}
