<?php

declare(strict_types=1);

namespace App\Services\Family\Http\Controllers\Child;

use App\Services\Family\Features\Child\AddChildFeature;
use App\Services\Family\Features\Child\UpdateChildFeature;
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
}
