<?php

declare(strict_types=1);

namespace App\Services\Church\Http\Controllers\Duplicate;

use App\Data\Models\DuplicateCandidate;
use App\Services\Church\Features\Duplicate\ResolveDuplicateFeature;
use Illuminate\Http\RedirectResponse;

final class Controller extends \Lucid\Units\Controller
{
    public function resolve(DuplicateCandidate $pair): RedirectResponse
    {
        return $this->serve(ResolveDuplicateFeature::class, ['pair' => $pair]);
    }
}
