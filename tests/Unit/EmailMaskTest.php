<?php

declare(strict_types=1);

use App\Foundation\Support\EmailMask;

it('keeps the first two letters and the domain', function (): void {
    expect(EmailMask::mask('janice@example.com'))->toBe('ja•••@example.com');
});

it('copes with a one-letter name and a missing @', function (): void {
    expect(EmailMask::mask('j@example.com'))->toBe('j•••@example.com')
        ->and(EmailMask::mask('nonsense'))->toBe('•••');
});
