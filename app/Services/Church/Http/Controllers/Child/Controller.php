<?php

declare(strict_types=1);

namespace App\Services\Church\Http\Controllers\Child;

use App\Data\Models\Child;
use App\Services\Church\Features\Child\DeleteChildPhotoFeature;
use App\Services\Church\Features\Child\RecordPhotoConsentFeature;
use App\Services\Church\Features\Child\ShowChildrenFeature;
use App\Services\Church\Features\Child\StaffAddChildPhotoFeature;
use App\Services\Church\Features\Child\UpdateChildDetailsFeature;
use App\Services\Church\Features\Child\UpdateChildLevelFeature;
use App\Services\Church\Features\Child\UpdateChildStatusFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class Controller extends \Lucid\Units\Controller
{
    public function index(): Response
    {
        return $this->serve(ShowChildrenFeature::class);
    }

    public function updateLevel(Child $child): RedirectResponse
    {
        return $this->serve(UpdateChildLevelFeature::class, ['child' => $child]);
    }

    public function update(Child $child): RedirectResponse
    {
        return $this->serve(UpdateChildDetailsFeature::class, ['child' => $child]);
    }

    public function updateStatus(Child $child): RedirectResponse
    {
        return $this->serve(UpdateChildStatusFeature::class, ['child' => $child]);
    }

    public function addPhoto(Child $child): RedirectResponse
    {
        return $this->serve(StaffAddChildPhotoFeature::class, ['child' => $child]);
    }

    public function recordPhotoConsent(Child $child): RedirectResponse
    {
        return $this->serve(RecordPhotoConsentFeature::class, ['child' => $child]);
    }

    public function deletePhoto(Child $child): RedirectResponse
    {
        return $this->serve(DeleteChildPhotoFeature::class, ['child' => $child]);
    }
}
