<?php

declare(strict_types=1);

use App\Enums\CardStepEnum;

/* Where a Card request is (GLOSSARY "Card request"): Asked → Approved → Printed → Given, or Refused. */

it('stores the five steps, in order', function (): void {
    expect(array_column(CardStepEnum::cases(), 'value'))->toBe(['asked', 'approved', 'printed', 'given', 'refused']);
});

it('starts every request at Asked', function (): void {
    expect(CardStepEnum::default())->toBe(CardStepEnum::Asked);
});

it('gives each step its label and says whether the request is still open — Given and Refused close it', function (CardStepEnum $step, string $label, bool $open): void {
    expect([$step->label(), $step->isOpen()])->toBe([$label, $open]);
})->with([
    [CardStepEnum::Asked, 'Asked', true],
    [CardStepEnum::Approved, 'Approved', true],
    [CardStepEnum::Printed, 'Printed', true],
    [CardStepEnum::Given, 'Given', false],
    [CardStepEnum::Refused, 'Refused', false],
]);

it('names the open steps — the ones the database holds to one per Child', function (): void {
    expect(CardStepEnum::openSteps())->toBe([CardStepEnum::Asked, CardStepEnum::Approved, CardStepEnum::Printed]);
});

it('is shown as plain words, never a coloured badge — a Card request\'s colour is its fee\'s (CardFeeEnum)', function (CardStepEnum $step): void {
    expect($step->color())->toBe('muted');
})->with(CardStepEnum::cases());
