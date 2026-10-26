<?php

declare(strict_types=1);

use App\Enums\CheckInResultEnum;

/* What happened to one child at the door: in, already in, or refused — and why, in the words the door says. */

it('has the outcomes of checking one child in', function (): void {
    expect(array_column(CheckInResultEnum::cases(), 'value'))
        ->toBe(['checked_in', 'already_in', 'graduated', 'inactive', 'needs_level']);
});

it('says which outcomes put the child in', function (CheckInResultEnum $result, bool $in): void {
    expect($result->isIn())->toBe($in);
})->with([
    [CheckInResultEnum::CheckedIn, true],
    [CheckInResultEnum::AlreadyIn, true],
    [CheckInResultEnum::Graduated, false],
    [CheckInResultEnum::Inactive, false],
    [CheckInResultEnum::NeedsLevel, false],
]);

it('says why a child was not checked in, in plain words', function (): void {
    expect(CheckInResultEnum::Graduated->label())->toBe('has Graduated from NxtGen')
        ->and(CheckInResultEnum::Inactive->label())->toBe('is inactive — make them active again first')
        ->and(CheckInResultEnum::NeedsLevel->label())->toBe('needs a Level first')
        ->and([CheckInResultEnum::CheckedIn->color(), CheckInResultEnum::Graduated->color()])->toBe(['success', 'danger']);
});
