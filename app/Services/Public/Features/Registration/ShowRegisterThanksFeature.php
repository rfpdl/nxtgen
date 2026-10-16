<?php

declare(strict_types=1);

namespace App\Services\Public\Features\Registration;

use App\Foundation\Attributes\UserStory;
use App\Services\Public\Features\BasePublicFeature;
use Inertia\Inertia;
use Inertia\Response;

/** @extends BasePublicFeature<Response> */
#[UserStory(
    title: 'See the thank-you page',
    persona: 'parent',
    happyPaths: ['story#7' => 'After registering, the parent sees a thank-you page'],
)]
final class ShowRegisterThanksFeature extends BasePublicFeature
{
    public function handle(): Response
    {
        return Inertia::render('Public/RegisterThanks');
    }
}
