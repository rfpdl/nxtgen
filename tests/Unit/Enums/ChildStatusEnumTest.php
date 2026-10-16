<?php

declare(strict_types=1);

use App\Enums\ChildStatusEnum;

/* Whether a child is still in NxtGen: active, inactive (went home, removed), or graduated out of the top Level. */

it('stores the three statuses', function (): void {
    expect(array_column(ChildStatusEnum::cases(), 'value'))->toBe(['active', 'inactive', 'graduated']);
});

it('registers every child as active', function (): void {
    expect(ChildStatusEnum::default())->toBe(ChildStatusEnum::Active);
});

it('gives each status its label and badge colour', function (ChildStatusEnum $status, string $label, string $color): void {
    expect([$status->label(), $status->color()])->toBe([$label, $color]);
})->with([
    [ChildStatusEnum::Active, 'Active', 'success'],
    [ChildStatusEnum::Inactive, 'Inactive', 'muted'],
    [ChildStatusEnum::Graduated, 'Graduated', 'info'],
]);

it('hands the page value, label and colour rows', function (): void {
    expect(ChildStatusEnum::toFrontend()[0])->toBe(['value' => 'active', 'label' => 'Active', 'color' => 'success'])
        ->and(ChildStatusEnum::toFrontend())->toHaveCount(3);
});
