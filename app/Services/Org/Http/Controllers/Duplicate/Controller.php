<?php

declare(strict_types=1);

namespace App\Services\Org\Http\Controllers\Duplicate;

use App\Services\Org\Features\Duplicate\ResolveDuplicateFeature;
use App\Services\Org\Features\Duplicate\ShowDuplicatesFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class Controller extends \Lucid\Units\Controller
{
    public function index(): Response
    {
        return $this->serve(ShowDuplicatesFeature::class);
    }

    public function resolve(string $organization, int $pair): RedirectResponse
    {
        return $this->serve(ResolveDuplicateFeature::class, ['pairId' => $pair]);
    }
}
