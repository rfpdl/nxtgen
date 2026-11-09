<?php

declare(strict_types=1);

namespace App\Services\Platform\Http\Controllers\People;

use App\Services\Platform\Features\People\GiveRoleFeature;
use App\Services\Platform\Features\People\RemoveRoleFeature;
use App\Services\Platform\Features\People\ShowPeopleFeature;
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
        return $this->serve(GiveRoleFeature::class);
    }

    public function destroy(int $role): RedirectResponse
    {
        return $this->serve(RemoveRoleFeature::class, ['roleId' => $role]);
    }
}
