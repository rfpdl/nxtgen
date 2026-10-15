<?php

declare(strict_types=1);

namespace App\Services\Org\Http\Controllers\Home;

use App\Services\Org\Features\Home\ShowOrgHomeFeature;
use Illuminate\Http\RedirectResponse;

final class Controller extends \Lucid\Units\Controller
{
    public function show(): RedirectResponse
    {
        return $this->serve(ShowOrgHomeFeature::class);
    }
}
