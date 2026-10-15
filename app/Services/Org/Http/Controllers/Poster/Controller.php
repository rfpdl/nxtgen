<?php

declare(strict_types=1);

namespace App\Services\Org\Http\Controllers\Poster;

use App\Services\Org\Features\Poster\ShowPosterFeature;
use Inertia\Response;

final class Controller extends \Lucid\Units\Controller
{
    public function show(): Response
    {
        return $this->serve(ShowPosterFeature::class);
    }
}
