<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Poster;

use App\Domains\Card\Jobs\CreateQrCodeSvgJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Services\Church\Features\BaseChurchFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The printable registration poster (plan §6 R1): this church's registration address as a QR code. Sent as an
 * image (data URI), never as markup to inject.
 *
 * @extends BaseChurchFeature<Response>
 */
#[AccessRow(AccessRowEnum::StaffArea)]
final class ShowPosterFeature extends BaseChurchFeature
{
    public function handle(): Response
    {
        $church = $this->church();
        $url = route('public.register', $church->slug);

        return Inertia::render('Church/Poster', [
            'church' => ['name' => $church->name, 'slug' => $church->slug],
            'registerUrl' => $url,
            'qr' => 'data:image/svg+xml;base64,'.base64_encode($this->run(new CreateQrCodeSvgJob(text: $url))),
        ]);
    }
}
