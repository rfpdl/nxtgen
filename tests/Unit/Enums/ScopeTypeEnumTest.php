<?php

declare(strict_types=1);

use App\Enums\ScopeTypeEnum;

/* Where a role holds: the whole Church, one Satellite, or one Schedule (shown as "class"). */

it('stores the three scopes', function (): void {
    expect(array_column(ScopeTypeEnum::cases(), 'value'))->toBe(['church', 'satellite', 'schedule']);
});

it('shows a Schedule as "Class", the word people use', function (ScopeTypeEnum $scope, string $label): void {
    expect([$scope->label(), $scope->color()])->toBe([$label, 'muted']);
})->with([
    [ScopeTypeEnum::Church, 'Church'],
    [ScopeTypeEnum::Satellite, 'Satellite'],
    [ScopeTypeEnum::Schedule, 'Class'],
]);
