<?php

declare(strict_types=1);

namespace App\Services\Org\Http\Controllers\Child;

use App\Services\Org\Features\Child\RecordPhotoConsentFeature;
use App\Services\Org\Features\Child\StaffAddChildPhotoFeature;
use App\Services\Org\Features\Child\UpdateChildDetailsFeature;
use App\Services\Org\Features\Child\UpdateChildStatusFeature;
use Illuminate\Http\RedirectResponse;

final class Controller extends \Lucid\Units\Controller
{
    public function update(string $organization, int $child): RedirectResponse
    {
        return $this->serve(UpdateChildDetailsFeature::class, ['childId' => $child]);
    }

    public function updateStatus(string $organization, int $child): RedirectResponse
    {
        return $this->serve(UpdateChildStatusFeature::class, ['childId' => $child]);
    }

    public function addPhoto(string $organization, int $child): RedirectResponse
    {
        return $this->serve(StaffAddChildPhotoFeature::class, ['childId' => $child]);
    }

    public function recordPhotoConsent(string $organization, int $child): RedirectResponse
    {
        return $this->serve(RecordPhotoConsentFeature::class, ['childId' => $child]);
    }
}
