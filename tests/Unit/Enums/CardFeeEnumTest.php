<?php

declare(strict_types=1);

use App\Enums\CardFeeEnum;
use Carbon\CarbonImmutable;

/* Where a Card request's fee stands: free (a first card), paid, or not paid — the one thing its Pill is coloured by. */

it('stores the three fee states', function (): void {
    expect(array_column(CardFeeEnum::cases(), 'value'))->toBe(['free', 'paid', 'not_paid']);
});

it('works out the state from the fee and when it was paid — a free card is free, paid or not', function (int $fee, ?string $paidAt, CardFeeEnum $state): void {
    expect(CardFeeEnum::of($fee, $paidAt === null ? null : CarbonImmutable::parse($paidAt)))->toBe($state);
})->with([
    'first card' => [0, null, CardFeeEnum::Free],
    'first card, marked paid' => [0, '2026-10-31 09:00', CardFeeEnum::Free],
    'AED 10, paid' => [1000, '2026-10-31 09:00', CardFeeEnum::Paid],
    'AED 10, not paid' => [1000, null, CardFeeEnum::NotPaid],
]);

it('gives each state its words and colour — only not paid stands out', function (CardFeeEnum $state, string $label, string $color): void {
    expect([$state->label(), $state->color()])->toBe([$label, $color]);
})->with([
    [CardFeeEnum::Free, 'free', 'info'],
    [CardFeeEnum::Paid, 'paid', 'info'],
    [CardFeeEnum::NotPaid, 'not paid', 'warning'],
]);
