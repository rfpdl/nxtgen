<?php

declare(strict_types=1);

use App\Enums\DuplicateReasonEnum;

/* Why two families or children were flagged as possibly the same. */

it('stores the two reasons', function (): void {
    expect(array_column(DuplicateReasonEnum::cases(), 'value'))->toBe(['same_phone', 'same_name_dob']);
});

it('says each reason in plain words, as a warning', function (DuplicateReasonEnum $reason, string $label): void {
    expect([$reason->label(), $reason->color()])->toBe([$label, 'warning']);
})->with([
    [DuplicateReasonEnum::SamePhone, 'Same phone'],
    [DuplicateReasonEnum::SameNameDob, 'Same name and birthday'],
]);
