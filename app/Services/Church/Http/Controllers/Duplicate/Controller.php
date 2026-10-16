<?php

declare(strict_types=1);

namespace App\Services\Church\Http\Controllers\Duplicate;

use App\Services\Church\Features\Duplicate\ResolveDuplicateFeature;
use App\Services\Church\Features\Duplicate\ShowDuplicatesFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class Controller extends \Lucid\Units\Controller
{
    public function index(): Response
    {
        return $this->serve(ShowDuplicatesFeature::class);
    }

    public function resolve(string $church, int $pair): RedirectResponse
    {
        return $this->serve(ResolveDuplicateFeature::class, ['pairId' => $pair]);
    }
}
