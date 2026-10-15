<?php

declare(strict_types=1);

namespace App\Services\Public\Features\Welcome;

use App\Services\Public\Features\BasePublicFeature;
use Inertia\Inertia;
use Inertia\Response;

/** @extends BasePublicFeature<Response> */
final class ShowWelcomeFeature extends BasePublicFeature
{
    public function handle(): Response
    {
        return Inertia::render('Public/Welcome');
    }
}
