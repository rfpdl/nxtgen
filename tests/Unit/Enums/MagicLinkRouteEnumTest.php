<?php

declare(strict_types=1);

use App\Enums\MagicLinkPurposeEnum;
use App\Enums\MagicLinkRouteEnum;

/* The one mapping between a link's stored purpose, its address and the route its Continue button posts to. */

it('gives every stored purpose its address', function (MagicLinkPurposeEnum $purpose, string $segment): void {
    expect(MagicLinkRouteEnum::fromPurpose($purpose)->value)->toBe($segment);
})->with([
    [MagicLinkPurposeEnum::Login, 'login'],
    [MagicLinkPurposeEnum::VerifyEmail, 'verify'],
    [MagicLinkPurposeEnum::ClaimFamily, 'claim'],
]);

it('names the route each address posts to', function (): void {
    expect(array_map(fn (MagicLinkRouteEnum $r): string => $r->routeName(), MagicLinkRouteEnum::cases()))
        ->toBe(['auth.magic.login', 'auth.magic.verify', 'auth.magic.claim'])
        ->and(MagicLinkRouteEnum::segments())->toBe(['login', 'verify', 'claim']);
});
