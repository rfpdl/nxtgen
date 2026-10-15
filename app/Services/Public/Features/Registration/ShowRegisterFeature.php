<?php

declare(strict_types=1);

namespace App\Services\Public\Features\Registration;

use App\Data\Models\Organization;
use App\Domains\Organization\Jobs\GetBranchesJob;
use App\Enums\GenderEnum;
use App\Enums\RelationshipEnum;
use App\Services\Public\Features\BasePublicFeature;
use Inertia\Inertia;
use Inertia\Response;

/** @extends BasePublicFeature<Response> */
final class ShowRegisterFeature extends BasePublicFeature
{
    public function __construct(private readonly Organization $organization) {}

    public function handle(): Response
    {
        return Inertia::render('Public/Register', [
            'church' => ['name' => $this->organization->name, 'slug' => $this->organization->slug],
            'satellites' => $this->run(new GetBranchesJob(organization: $this->organization)),
            'relationships' => RelationshipEnum::toFrontend(),
            'genders' => GenderEnum::toFrontend(),
        ]);
    }
}
