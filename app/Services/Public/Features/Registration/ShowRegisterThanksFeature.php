<?php

declare(strict_types=1);

namespace App\Services\Public\Features\Registration;

use App\Data\Models\Church;
use App\Services\Public\Features\BasePublicFeature;
use Inertia\Inertia;
use Inertia\Response;

/** @extends BasePublicFeature<Response> */
final class ShowRegisterThanksFeature extends BasePublicFeature
{
    public function __construct(private readonly Church $church) {}

    public function handle(): Response
    {
        return Inertia::render('Public/RegisterThanks', [
            'church' => ['name' => $this->church->name, 'slug' => $this->church->slug],
        ]);
    }
}
