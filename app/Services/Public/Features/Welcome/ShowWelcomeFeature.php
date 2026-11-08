<?php

declare(strict_types=1);

namespace App\Services\Public\Features\Welcome;

use App\Foundation\Attributes\UserStory;
use App\Services\Public\Features\BasePublicFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The welcome page (Roy 2026-11-08): empty for now — the bar with Sign in. A family registers at its own satellite's
 * address (`/{satellite}/register`, from that satellite's QR code or link), so the page links to no form.
 *
 * @extends BasePublicFeature<Response>
 */
#[UserStory(
    title: 'See the welcome page',
    persona: 'visitor',
    happyPaths: ['story#1' => 'A visitor who is not signed in sees the NxtGen welcome page, with Sign in'],
)]
final class ShowWelcomeFeature extends BasePublicFeature
{
    public function handle(): Response
    {
        return Inertia::render('Public/Welcome');
    }
}
