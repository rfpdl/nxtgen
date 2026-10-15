<?php

declare(strict_types=1);

namespace App\Services\Family\Http\Controllers\Home;

use App\Services\Family\Features\Home\ShowFamilyFeature;
use Inertia\Response;

final class Controller extends \Lucid\Units\Controller
{
    public function show(): Response
    {
        return $this->serve(ShowFamilyFeature::class);
    }
}
