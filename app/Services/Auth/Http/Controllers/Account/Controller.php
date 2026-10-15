<?php

declare(strict_types=1);

namespace App\Services\Auth\Http\Controllers\Account;

use App\Services\Auth\Features\Account\ShowPasswordFeature;
use App\Services\Auth\Features\Account\UpdatePasswordFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class Controller extends \Lucid\Units\Controller
{
    public function showPassword(): Response
    {
        return $this->serve(ShowPasswordFeature::class);
    }

    public function updatePassword(): RedirectResponse
    {
        return $this->serve(UpdatePasswordFeature::class);
    }
}
