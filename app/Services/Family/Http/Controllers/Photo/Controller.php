<?php

declare(strict_types=1);

namespace App\Services\Family\Http\Controllers\Photo;

use App\Data\Models\Child;
use App\Services\Family\Features\Photo\ShowChildPhotoFeature;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class Controller extends \Lucid\Units\Controller
{
    public function child(Child $child): StreamedResponse
    {
        return $this->serve(ShowChildPhotoFeature::class, ['child' => $child]);
    }
}
