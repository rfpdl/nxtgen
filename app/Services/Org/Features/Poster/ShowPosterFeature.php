<?php

declare(strict_types=1);

namespace App\Services\Org\Features\Poster;

use App\Domains\Card\Jobs\CreateQrCodeSvgJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Services\Org\Features\BaseOrgFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The printable registration poster (plan §6 R1): this church's registration address as a QR code. Sent as an
 * image (data URI), never as markup to inject.
 *
 * @extends BaseOrgFeature<Response>
 */
#[AccessRow(AccessRowEnum::StaffArea)]
final class ShowPosterFeature extends BaseOrgFeature
{
    public function handle(): Response
    {
        $org = $this->organization();
        $url = route('public.register', $org->slug);

        return Inertia::render('Org/Poster', [
            'church' => ['name' => $org->name, 'slug' => $org->slug],
            'registerUrl' => $url,
            'qr' => 'data:image/svg+xml;base64,'.base64_encode($this->run(new CreateQrCodeSvgJob(text: $url))),
        ]);
    }
}
