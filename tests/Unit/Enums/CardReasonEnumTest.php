<?php

declare(strict_types=1);

use App\Enums\CardReasonEnum;

/* Why a Card request was made (GLOSSARY "Card request"): Lost, Damaged, or a child's First card — which is free. */

it('stores the three reasons', function (): void {
    expect(array_column(CardReasonEnum::cases(), 'value'))->toBe(['lost', 'damaged', 'first_card']);
});

it('gives each reason its label and fee — AED 10 for a lost or damaged card, the first card free', function (CardReasonEnum $reason, string $label, int $fee): void {
    expect([$reason->label(), $reason->feeMinor()])->toBe([$label, $fee]);
})->with([
    [CardReasonEnum::Lost, 'Lost', 1000],
    [CardReasonEnum::Damaged, 'Damaged', 1000],
    [CardReasonEnum::FirstCard, 'First card', 0],
]);

it('lets staff file only a lost or damaged card — a first card is made at registration', function (): void {
    expect(CardReasonEnum::staffChoices())->toBe([CardReasonEnum::Lost, CardReasonEnum::Damaged])
        ->and(CardReasonEnum::toFrontend(CardReasonEnum::staffChoices()))->toBe([
            ['value' => 'lost', 'label' => 'Lost', 'color' => 'muted'],
            ['value' => 'damaged', 'label' => 'Damaged', 'color' => 'muted'],
        ]);
});

it('is shown as plain text, never as a badge', function (CardReasonEnum $reason): void {
    expect($reason->color())->toBe('muted');
})->with(CardReasonEnum::cases());
