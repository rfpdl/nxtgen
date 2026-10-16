<?php

declare(strict_types=1);

namespace App\Services\Family\Http\Controllers\Child;

use App\Services\Family\Features\Child\AddChildFeature;
use App\Services\Family\Features\Child\AddChildPhotoFeature;
use App\Services\Family\Features\Child\GivePhotoConsentFeature;
use App\Services\Family\Features\Child\UpdateChildFeature;
use App\Services\Family\Features\Child\WithdrawPhotoConsentFeature;
use Illuminate\Http\RedirectResponse;

final class Controller extends \Lucid\Units\Controller
{
    public function store(): RedirectResponse
    {
        return $this->serve(AddChildFeature::class);
    }

    public function update(string $organization, int $child): RedirectResponse
    {
        return $this->serve(UpdateChildFeature::class, ['childId' => $child]);
    }

    public function addPhoto(string $organization, int $child): RedirectResponse
    {
        return $this->serve(AddChildPhotoFeature::class, ['childId' => $child]);
    }

    public function givePhotoConsent(string $organization, int $child): RedirectResponse
    {
        return $this->serve(GivePhotoConsentFeature::class, ['childId' => $child]);
    }

    public function withdrawPhotoConsent(string $organization, int $child): RedirectResponse
    {
        return $this->serve(WithdrawPhotoConsentFeature::class, ['childId' => $child]);
    }
}
