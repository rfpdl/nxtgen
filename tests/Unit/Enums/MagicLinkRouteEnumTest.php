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

it('words the page each link opens: its title, what pressing does, and the button (TODO #41: never retyped in Vue)', function (): void {
    expect(array_map(fn (MagicLinkRouteEnum $r): array => [$r->label(), $r->prompt(), $r->button()], MagicLinkRouteEnum::cases()))->toBe([
        ['Sign in', 'Press the button to finish signing in.', 'Continue to sign in'],
        ['Confirm your email', 'Press the button to tell us this email is yours.', 'Yes, this is my email'],
        ['Connect your family', 'Press the button to connect your family to this email.', 'Connect my family'],
    ]);
});
