<?php

declare(strict_types=1);

use App\Enums\FamilyClaimStatusEnum;

/* A request to connect a family to an account that already exists: pending until the link is used or withdrawn. */

it('stores the three states', function (): void {
    expect(array_column(FamilyClaimStatusEnum::cases(), 'value'))->toBe(['pending', 'accepted', 'revoked']);
});

it('starts every claim pending', function (): void {
    expect(FamilyClaimStatusEnum::default())->toBe(FamilyClaimStatusEnum::Pending);
});

it('gives each state its label and badge colour', function (FamilyClaimStatusEnum $status, string $label, string $color): void {
    expect([$status->label(), $status->color()])->toBe([$label, $color]);
})->with([
    [FamilyClaimStatusEnum::Pending, 'Pending', 'warning'],
    [FamilyClaimStatusEnum::Accepted, 'Accepted', 'success'],
    [FamilyClaimStatusEnum::Revoked, 'Revoked', 'muted'],
]);
