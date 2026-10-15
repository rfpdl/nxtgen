<?php

declare(strict_types=1);

namespace App\Services\Org\Http\Controllers\Child;

use App\Services\Org\Features\Child\UpdateChildStatusFeature;
use Illuminate\Http\RedirectResponse;

final class Controller extends \Lucid\Units\Controller
{
    public function updateStatus(string $organization, int $child): RedirectResponse
    {
        return $this->serve(UpdateChildStatusFeature::class, ['childId' => $child]);
    }
}
