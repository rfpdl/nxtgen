<?php

declare(strict_types=1);

namespace App\Services\Church\Http\Controllers\MovingUp;

use App\Data\Models\Child;
use App\Data\Models\MovingUp;
use App\Services\Church\Features\MovingUp\MoveChildrenUpFeature;
use App\Services\Church\Features\MovingUp\PutBackChildFeature;
use App\Services\Church\Features\MovingUp\PutBackEveryoneFeature;
use App\Services\Church\Features\MovingUp\ShowMovingUpFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class Controller extends \Lucid\Units\Controller
{
    public function index(): Response
    {
        return $this->serve(ShowMovingUpFeature::class);
    }

    public function store(): RedirectResponse
    {
        return $this->serve(MoveChildrenUpFeature::class);
    }

    public function putBackChild(MovingUp $movingUp, Child $child): RedirectResponse
    {
        return $this->serve(PutBackChildFeature::class, ['movingUp' => $movingUp, 'child' => $child]);
    }

    public function putBackEveryone(MovingUp $movingUp): RedirectResponse
    {
        return $this->serve(PutBackEveryoneFeature::class, ['movingUp' => $movingUp]);
    }
}
