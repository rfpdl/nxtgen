<?php

declare(strict_types=1);

namespace App\Services\Public\Features\Registration;

use App\Data\Models\Organization;
use App\Services\Public\Features\BasePublicFeature;
use Inertia\Inertia;
use Inertia\Response;

/** @extends BasePublicFeature<Response> */
final class ShowRegisterThanksFeature extends BasePublicFeature
{
    public function __construct(private readonly Organization $organization) {}

    public function handle(): Response
    {
        return Inertia::render('Public/RegisterThanks', [
            'church' => ['name' => $this->organization->name, 'slug' => $this->organization->slug],
        ]);
    }
}
