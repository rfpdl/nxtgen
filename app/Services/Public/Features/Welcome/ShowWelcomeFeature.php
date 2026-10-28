<?php

declare(strict_types=1);

namespace App\Services\Public\Features\Welcome;

use App\Domains\Church\Jobs\GetChurchBySlugJob;
use App\Foundation\Attributes\UserStory;
use App\Services\Public\Features\BasePublicFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The page links to the registration form of the church this install serves (`nxtgen.church.slug`, the one
 * ChurchSeeder creates): the welcome page's "Register your family" (ui-theme issue 03). Null when that church is
 * not there, so the page shows Sign in only and never fails.
 *
 * @extends BasePublicFeature<Response>
 */
#[UserStory(
    title: 'See the welcome page',
    persona: 'visitor',
    happyPaths: ['story#1' => 'A visitor who is not signed in sees the NxtGen welcome page, with no church needed'],
)]
final class ShowWelcomeFeature extends BasePublicFeature
{
    public function handle(): Response
    {
        $church = $this->run(new GetChurchBySlugJob(slug: config()->string('nxtgen.church.slug')));

        return Inertia::render('Public/Welcome', [
            'register_url' => $church === null ? null : route('public.register', $church->slug),
        ]);
    }
}
