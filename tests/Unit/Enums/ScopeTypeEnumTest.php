<?php

declare(strict_types=1);

use App\Enums\ScopeTypeEnum;

/*
 * Where a role holds: one Satellite, or one Schedule (shown as "class"). There is no church-wide Role (spec item 9):
 * every admin is a satellite's or a class's.
 */

it('stores the two scopes, and no church-wide one', function (): void {
    expect(array_column(ScopeTypeEnum::cases(), 'value'))->toBe(['satellite', 'schedule']);
});

it('shows a Schedule as "Class", the word people use', function (ScopeTypeEnum $scope, string $label): void {
    expect([$scope->label(), $scope->color()])->toBe([$label, 'muted']);
})->with([
    [ScopeTypeEnum::Satellite, 'Satellite'],
    [ScopeTypeEnum::Schedule, 'Class'],
]);
