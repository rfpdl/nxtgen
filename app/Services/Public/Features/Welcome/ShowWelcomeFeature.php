<?php

declare(strict_types=1);

namespace App\Services\Public\Features\Welcome;

use App\Foundation\Attributes\UserStory;
use App\Services\Public\Features\BasePublicFeature;
use Inertia\Inertia;
use Inertia\Response;

/** @extends BasePublicFeature<Response> */
#[UserStory(
    title: 'See the welcome page',
    persona: 'visitor',
    happyPaths: ['story#1' => 'A visitor who is not signed in sees the NxtGen welcome page, with no church needed'],
)]
final class ShowWelcomeFeature extends BasePublicFeature
{
    public function handle(): Response
    {
        return Inertia::render('Public/Welcome');
    }
}
