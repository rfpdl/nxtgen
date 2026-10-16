<?php

declare(strict_types=1);

namespace App\Services\Auth\Features\Link;

use App\Enums\MagicLinkRouteEnum;
use App\Services\Auth\Features\BaseAuthFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Opening an emailed link only shows a "Continue" button. Email security scanners fetch links before people
 * do; if GET used the link, the scanner would spend it (or accept a family claim) — Codex diff review, high.
 * The button POSTs, with CSRF, and only that uses the link — to the route MagicLinkRouteEnum names.
 *
 * @extends BaseAuthFeature<Response>
 */
final class ShowConfirmLinkFeature extends BaseAuthFeature
{
    public function __construct(
        private readonly MagicLinkRouteEnum $purpose,
        private readonly string $token,
    ) {}

    public function handle(): Response
    {
        return Inertia::render('Auth/ConfirmLink', [
            'purpose' => $this->purpose,
            'action' => route($this->purpose->routeName(), $this->token),
        ]);
    }
}
