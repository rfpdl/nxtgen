<?php

declare(strict_types=1);

use App\Domains\Card\Jobs\CreateQrCodeSvgJob;

/* A QR code drawn on the server as SVG — the same text always draws the same code, different text a different one. */

it('draws an SVG that depends only on the text', function (): void {
    $svg = (new CreateQrCodeSvgJob(text: 'https://nxtgen.test/register'))->handle();

    expect($svg)->toContain('<svg')
        ->and($svg)->toContain('</svg>')
        ->and((new CreateQrCodeSvgJob(text: 'https://nxtgen.test/register'))->handle())->toBe($svg)
        ->and((new CreateQrCodeSvgJob(text: 'https://nxtgen.test/other'))->handle())->not->toBe($svg);
});
