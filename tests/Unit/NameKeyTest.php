<?php

declare(strict_types=1);

use App\Foundation\Support\NameKey;

/*
 * The name-card key (slice 3, the NXTGEN name cards): the child's full name in capitals, letters only, no spaces —
 * "Adam Matthias Abergos" → ADAMMATTHIASABERGOS — what the Code 39 barcode on the card spells.
 */

it('spells a full name the way the card does', function (string $first, ?string $middle, string $last, string $key): void {
    expect(NameKey::of($first, $middle, $last))->toBe($key);
})->with([
    'the card\'s own example' => ['Adam', 'Matthias', 'Abergos', 'ADAMMATTHIASABERGOS'],
    'no middle name' => ['Amara', null, 'Reyes', 'AMARAREYES'],
    'an empty middle name' => ['Amara', '', 'Reyes', 'AMARAREYES'],
    'accents removed' => ['José', 'Ángel', 'Peñaflor', 'JOSEANGELPENAFLOR'],
    'ñ becomes N' => ['Niño', null, 'Ñunez', 'NINONUNEZ'],
    'spaces, hyphens and apostrophes dropped' => ['Mary Grace', null, "D'Souza-Lim", 'MARYGRACEDSOUZALIM'],
    'digits and dots dropped' => ['Jr. 2', null, 'Cruz', 'JRCRUZ'],
]);

it('reads a scanned or typed code the same way', function (): void {
    expect(NameKey::code(' adam-matthias abergos '))->toBe('ADAMMATTHIASABERGOS')
        ->and(NameKey::code('Ádam'))->toBe('ADAM');
});

it('says whether a search term could be a card code: one word of letters, two or more', function (string $term, bool $could): void {
    expect(NameKey::looksLikeCode($term))->toBe($could);
})->with([
    'a card code' => ['ADAMMATTHIASABERGOS', true],
    'typed in lower case' => ['amarajoyreyes', true],
    'two words' => ['Amara Reyes', false],
    'with digits' => ['050123', false],
    'a short name' => ['BONG', true],
    'one letter' => ['A', false],
]);

it('gives the keys a child is stored with: the full name, and the first and last names alone', function (): void {
    expect(NameKey::keysFor('Adam', 'Matthias', 'Abergos'))->toBe(['name_key' => 'ADAMMATTHIASABERGOS', 'name_key_first' => 'ADAM', 'name_key_last' => 'ABERGOS']);
});
