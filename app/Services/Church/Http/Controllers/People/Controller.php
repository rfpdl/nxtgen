<?php

declare(strict_types=1);

namespace App\Services\Church\Http\Controllers\People;

use App\Services\Church\Features\People\AddStaffFeature;
use App\Services\Church\Features\People\RemoveStaffRoleFeature;
use App\Services\Church\Features\People\ShowPeopleFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class Controller extends \Lucid\Units\Controller
{
    public function index(): Response
    {
        return $this->serve(ShowPeopleFeature::class);
    }

    public function store(): RedirectResponse
    {
        return $this->serve(AddStaffFeature::class);
    }

    public function removeRole(string $church, int $assignment): RedirectResponse
    {
        return $this->serve(RemoveStaffRoleFeature::class, ['assignmentId' => $assignment]);
    }
}
