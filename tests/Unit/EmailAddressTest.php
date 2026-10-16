<?php

declare(strict_types=1);

use App\Foundation\Support\EmailAddress;

/* The one way an email is compared and stored: trimmed and lower-cased (plan §4.2 — one login per address). */

it('trims and lower-cases an address', function (): void {
    expect(EmailAddress::normalise('  Janice.Santos@Example.COM '))->toBe('janice.santos@example.com');
});

it('lower-cases letters beyond ASCII', function (): void {
    expect(EmailAddress::normalise('ÉLODIE@example.com'))->toBe('élodie@example.com');
});

it('leaves a blank address blank', function (): void {
    expect(EmailAddress::normalise('   '))->toBe('');
});
