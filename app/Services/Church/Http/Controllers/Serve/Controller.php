<?php

declare(strict_types=1);

namespace App\Services\Church\Http\Controllers\Serve;

use App\Services\Church\Features\Serve\ShowServeFeature;
use Inertia\Response;

/** Serve's first page (spec items 16–17): today's classes at the satellite, open ones a link to their door. */
final class Controller extends \Lucid\Units\Controller
{
    public function show(): Response
    {
        return $this->serve(ShowServeFeature::class);
    }
}
