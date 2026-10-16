<?php

declare(strict_types=1);

use App\Enums\MagicLinkPurposeEnum;

/* What an emailed link is for: signing in, confirming the email, or connecting a family to an account. */

it('stores the three purposes', function (): void {
    expect(array_column(MagicLinkPurposeEnum::cases(), 'value'))->toBe(['login', 'verify_email', 'claim_family']);
});

it('names each purpose in plain words', function (MagicLinkPurposeEnum $purpose, string $label): void {
    expect([$purpose->label(), $purpose->color()])->toBe([$label, 'muted']);
})->with([
    [MagicLinkPurposeEnum::Login, 'Login'],
    [MagicLinkPurposeEnum::VerifyEmail, 'Confirm email'],
    [MagicLinkPurposeEnum::ClaimFamily, 'Claim family'],
]);
