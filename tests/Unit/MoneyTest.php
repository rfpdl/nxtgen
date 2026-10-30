<?php

declare(strict_types=1);

use App\Foundation\Support\Money;

/* An amount in fils, as people read it in dirhams: whole dirhams plain, fils never dropped. */

it('says an amount in dirhams, keeping the fils', function (int $minor, string $said): void {
    expect(Money::aed($minor))->toBe($said);
})->with([
    [1000, 'AED 10'],
    [1050, 'AED 10.50'],
    [5, 'AED 0.05'],
    [0, 'AED 0'],
    [123456, 'AED 1,234.56'],
]);
