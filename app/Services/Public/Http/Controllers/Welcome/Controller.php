<?php

declare(strict_types=1);

namespace App\Services\Public\Http\Controllers\Welcome;

use App\Services\Public\Features\Welcome\ShowWelcomeFeature;
use Inertia\Response;

final class Controller extends \Lucid\Units\Controller
{
    public function show(): Response
    {
        return $this->serve(ShowWelcomeFeature::class);
    }
}
