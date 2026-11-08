<?php

declare(strict_types=1);

namespace App\Services\Public\Http\Controllers\Registration;

use App\Data\Models\Satellite;
use App\Services\Public\Features\Registration\RegisterFamilyFeature;
use App\Services\Public\Features\Registration\ShowRegisterFeature;
use App\Services\Public\Features\Registration\ShowRegisterThanksFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class Controller extends \Lucid\Units\Controller
{
    public function show(Satellite $satellite): Response|RedirectResponse
    {
        return $this->serve(ShowRegisterFeature::class, ['satellite' => $satellite]);
    }

    public function store(Satellite $satellite): RedirectResponse
    {
        return $this->serve(RegisterFamilyFeature::class, ['satellite' => $satellite]);
    }

    /** The `{satellite}` binding is what turns an unknown satellite into a 404; the page reads the shared `church`. */
    public function thanks(Satellite $satellite): Response
    {
        return $this->serve(ShowRegisterThanksFeature::class);
    }
}
