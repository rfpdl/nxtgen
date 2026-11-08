<?php

declare(strict_types=1);

use App\Foundation\Support\SatelliteCode;

/* A registration code: 8 lowercase letters and digits, never one that looks like another (Roy 2026-11-08). */

it('is 8 letters and digits with no look-alikes', function (): void {
    foreach (range(1, 200) as $_) {
        expect(SatelliteCode::random())->toMatch('/^[2-9a-hjkmnp-z]{8}$/');
    }
});

// Astra 2026-11-09: a satellite address never has a code's shape, so an address can never take over another
// satellite's printed QR (the address is looked up first).
it('knows a code\'s shape — and an address never has it', function (string $text, bool $codeShaped): void {
    expect(SatelliteCode::looksLikeOne($text))->toBe($codeShaped);
})->with([
    'a code' => ['k7m2q9xa', true],
    'a random one' => [SatelliteCode::random(), true],
    'dubai' => ['dubai', false],
    'abu-dhabi' => ['abu-dhabi', false],
    'eight letters with an i' => ['sharjahi', false],
    'eight look-alike-free letters' => ['sharjahs', true],
]);
