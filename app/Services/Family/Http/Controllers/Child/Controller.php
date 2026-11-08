<?php

declare(strict_types=1);

namespace App\Services\Family\Http\Controllers\Child;

use App\Data\Models\Child;
use App\Services\Family\Features\Child\AddChildFeature;
use App\Services\Family\Features\Child\AddChildPhotoFeature;
use App\Services\Family\Features\Child\DeleteChildPhotoFeature;
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

    public function update(Child $child): RedirectResponse
    {
        return $this->serve(UpdateChildFeature::class, ['child' => $child]);
    }

    public function addPhoto(Child $child): RedirectResponse
    {
        return $this->serve(AddChildPhotoFeature::class, ['child' => $child]);
    }

    public function givePhotoConsent(Child $child): RedirectResponse
    {
        return $this->serve(GivePhotoConsentFeature::class, ['child' => $child]);
    }

    public function withdrawPhotoConsent(Child $child): RedirectResponse
    {
        return $this->serve(WithdrawPhotoConsentFeature::class, ['child' => $child]);
    }

    public function deletePhoto(Child $child): RedirectResponse
    {
        return $this->serve(DeleteChildPhotoFeature::class, ['child' => $child]);
    }
}
