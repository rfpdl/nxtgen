<?php

declare(strict_types=1);

use App\Enums\CardListTabEnum;
use App\Enums\CardStepEnum;

/* The Cards page's tabs: one per step still to do, and one for the closed requests (Given or Refused). */

it('stores the four tabs, in the order a request moves', function (): void {
    expect(array_column(CardListTabEnum::cases(), 'value'))->toBe(['asked', 'approved', 'printed', 'closed']);
});

it('opens on Asked — what is waiting for an admin', function (): void {
    expect(CardListTabEnum::default())->toBe(CardListTabEnum::Asked);
});

it('names each tab and the steps it lists', function (CardListTabEnum $tab, string $label, array $steps): void {
    expect([$tab->label(), $tab->steps(), $tab->color()])->toBe([$label, $steps, 'muted']);
})->with([
    [CardListTabEnum::Asked, 'Asked', [CardStepEnum::Asked]],
    [CardListTabEnum::Approved, 'Approved', [CardStepEnum::Approved]],
    [CardListTabEnum::Printed, 'Printed', [CardStepEnum::Printed]],
    [CardListTabEnum::Closed, 'Given or refused', [CardStepEnum::Given, CardStepEnum::Refused]],
]);
