<?php

declare(strict_types=1);

namespace App\Services\Church\Http\Controllers\CardRequest;

use App\Services\Church\Features\CardRequest\FileCardRequestFeature;
use Illuminate\Http\RedirectResponse;

final class Controller extends \Lucid\Units\Controller
{
    public function store(string $church, int $child): RedirectResponse
    {
        return $this->serve(FileCardRequestFeature::class, ['childId' => $child]);
    }
}
