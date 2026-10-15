<?php

declare(strict_types=1);

namespace App\Domains\Card\Jobs;

use App\Foundation\Jobs\BaseJob;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * A QR code as SVG, drawn on the server — no third-party service sees the address. Vector, so it prints sharp at
 * any size (the poster now; the children's QR cards after the February Moving-Up).
 *
 * @extends BaseJob<string>
 */
final class CreateQrCodeSvgJob extends BaseJob
{
    public function __construct(private readonly string $text) {}

    public function handle(): string
    {
        return (new Writer(new ImageRenderer(new RendererStyle(400, 2), new SvgImageBackEnd)))->writeString($this->text);
    }
}
