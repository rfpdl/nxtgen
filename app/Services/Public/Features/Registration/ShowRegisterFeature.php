<?php

declare(strict_types=1);

namespace App\Services\Public\Features\Registration;

use App\Data\Models\Church;
use App\Domains\Church\Jobs\GetSatellitesJob;
use App\Enums\GenderEnum;
use App\Enums\RelationshipEnum;
use App\Services\Public\Features\BasePublicFeature;
use Inertia\Inertia;
use Inertia\Response;

/** @extends BasePublicFeature<Response> */
final class ShowRegisterFeature extends BasePublicFeature
{
    public function __construct(private readonly Church $church) {}

    public function handle(): Response
    {
        return Inertia::render('Public/Register', [
            'church' => ['name' => $this->church->name, 'slug' => $this->church->slug],
            'satellites' => $this->run(new GetSatellitesJob(church: $this->church)),
            'relationships' => RelationshipEnum::toFrontend(),
            'genders' => GenderEnum::toFrontend(),
        ]);
    }
}
