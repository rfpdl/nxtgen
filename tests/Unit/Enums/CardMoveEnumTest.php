<?php

declare(strict_types=1);

use App\Enums\CardFeeEnum;
use App\Enums\CardMoveEnum;
use App\Enums\CardStepEnum;

/*
 * The moves a Card request makes (name cards 03) — the ONE place that says which move may start from which step, and
 * what the fee must be. The move Jobs' conditions and the moves sent to the screen both read it.
 */

it('stores the five moves', function (): void {
    expect(array_column(CardMoveEnum::cases(), 'value'))->toBe(['mark_paid', 'approve', 'refuse', 'mark_printed', 'give']);
});

it('names the steps each move starts from', function (CardMoveEnum $move, array $from): void {
    expect($move->fromSteps())->toBe($from);
})->with([
    [CardMoveEnum::MarkPaid, [CardStepEnum::Asked, CardStepEnum::Approved, CardStepEnum::Printed]],
    [CardMoveEnum::Approve, [CardStepEnum::Asked]],
    [CardMoveEnum::Refuse, [CardStepEnum::Asked, CardStepEnum::Approved]],
    [CardMoveEnum::MarkPrinted, [CardStepEnum::Approved]],
    [CardMoveEnum::Give, [CardStepEnum::Printed]],
]);

it('lists the moves a request may make, by step and fee — pay while not paid, approve only once paid or free', function (CardStepEnum $step, CardFeeEnum $fee, array $moves): void {
    expect(CardMoveEnum::allowedFor($step, $fee))->toBe($moves);
})->with([
    'asked, not paid' => [CardStepEnum::Asked, CardFeeEnum::NotPaid, [CardMoveEnum::MarkPaid, CardMoveEnum::Refuse]],
    'asked, paid' => [CardStepEnum::Asked, CardFeeEnum::Paid, [CardMoveEnum::Approve, CardMoveEnum::Refuse]],
    'asked, free' => [CardStepEnum::Asked, CardFeeEnum::Free, [CardMoveEnum::Approve, CardMoveEnum::Refuse]],
    'approved' => [CardStepEnum::Approved, CardFeeEnum::Paid, [CardMoveEnum::Refuse, CardMoveEnum::MarkPrinted]],
    'printed' => [CardStepEnum::Printed, CardFeeEnum::Paid, [CardMoveEnum::Give]],
    'printed, still not paid' => [CardStepEnum::Printed, CardFeeEnum::NotPaid, [CardMoveEnum::MarkPaid, CardMoveEnum::Give]],
    'given' => [CardStepEnum::Given, CardFeeEnum::Paid, []],
    'refused' => [CardStepEnum::Refused, CardFeeEnum::NotPaid, []],
]);

it('gives each move the words on its button', function (CardMoveEnum $move, string $label): void {
    expect([$move->label(), $move->color()])->toBe([$label, 'muted']);
})->with([
    [CardMoveEnum::MarkPaid, 'Mark paid'],
    [CardMoveEnum::Approve, 'Approve'],
    [CardMoveEnum::Refuse, 'Refuse…'],
    [CardMoveEnum::MarkPrinted, 'Mark printed'],
    [CardMoveEnum::Give, 'Given'],
]);
