<?php

declare(strict_types=1);

namespace App\Services\Family\Features\Home;

use App\Domains\Family\Jobs\GetFamilyPortalJob;
use App\Enums\GenderEnum;
use App\Services\Family\Features\BaseFamilyFeature;
use Inertia\Inertia;
use Inertia\Response;

/** @extends BaseFamilyFeature<Response> */
final class ShowFamilyFeature extends BaseFamilyFeature
{
    public function handle(): Response
    {
        $family = $this->family();

        return Inertia::render('Family/Show', [
            'church' => ['name' => $family->organization->name, 'slug' => $family->organization->slug],
            'family' => $this->run(new GetFamilyPortalJob(family: $family)),
            'genders' => GenderEnum::toFrontend(),
        ]);
    }
}
