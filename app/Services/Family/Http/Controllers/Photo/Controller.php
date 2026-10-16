<?php

declare(strict_types=1);

namespace App\Services\Family\Http\Controllers\Photo;

use App\Services\Family\Features\Photo\ShowChildPhotoFeature;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class Controller extends \Lucid\Units\Controller
{
    public function child(int $child): StreamedResponse
    {
        return $this->serve(ShowChildPhotoFeature::class, ['childId' => $child]);
    }
}
