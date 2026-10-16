<?php

declare(strict_types=1);

namespace App\Services\Church\Http\Controllers\Home;

use App\Services\Church\Features\Home\ShowChurchHomeFeature;
use Illuminate\Http\RedirectResponse;

final class Controller extends \Lucid\Units\Controller
{
    public function show(): RedirectResponse
    {
        return $this->serve(ShowChurchHomeFeature::class);
    }
}
