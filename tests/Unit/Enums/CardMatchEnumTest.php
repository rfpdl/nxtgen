<?php

declare(strict_types=1);

use App\Enums\CardMatchEnum;

/* How a name card matched at the door: only one exact match ticks a child; anything else asks staff to look. */

it('has the ways a card can match', function (): void {
    expect(array_column(CardMatchEnum::cases(), 'value'))->toBe(['exact', 'several', 'close', 'none']);
});

it('ticks a child only on one exact match', function (CardMatchEnum $match, bool $ticks): void {
    expect($match->ticks())->toBe($ticks);
})->with([
    [CardMatchEnum::Exact, true],
    [CardMatchEnum::Several, false],
    [CardMatchEnum::Close, false],
    [CardMatchEnum::None, false],
]);

it('says what staff should do, in plain words — nothing for one exact match', function (): void {
    expect(CardMatchEnum::Exact->note('AMARAREYES'))->toBeNull()
        ->and(CardMatchEnum::Several->note('AMARAREYES'))->toBe('More than one child matches this card — tick the right one.')
        ->and(CardMatchEnum::Close->note('AMARAREYES'))->toBe('Check this is the right child — the name on the card is not an exact match.')
        ->and(CardMatchEnum::None->note('AMARAREYES'))->toBe('No child matches this card: AMARAREYES')
        ->and([CardMatchEnum::Exact->color(), CardMatchEnum::Close->color(), CardMatchEnum::None->color()])->toBe(['success', 'warning', 'danger']);
});
