<?php

declare(strict_types=1);

namespace App\Services\Church\Http\Controllers\Duplicate;

use App\Services\Church\Features\Duplicate\ResolveDuplicateFeature;
use Illuminate\Http\RedirectResponse;

final class Controller extends \Lucid\Units\Controller
{
    public function resolve(string $church, int $pair): RedirectResponse
    {
        return $this->serve(ResolveDuplicateFeature::class, ['pairId' => $pair]);
    }
}
