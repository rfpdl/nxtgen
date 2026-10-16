<?php

declare(strict_types=1);

use App\Enums\DuplicateStatusEnum;

/* A reviewer's answer to a possible duplicate, in the one wording the history note and the reply both use. */

it('says what each answer means', function (DuplicateStatusEnum $status, string $answer): void {
    expect($status->answer())->toBe($answer);
})->with([
    [DuplicateStatusEnum::Dismissed, 'not the same family'],
    [DuplicateStatusEnum::Confirmed, 'the same family, flagged for merging'],
    [DuplicateStatusEnum::Open, 'not answered yet'],
]);

it('starts every pair open', function (): void {
    expect(DuplicateStatusEnum::default())->toBe(DuplicateStatusEnum::Open);
});
