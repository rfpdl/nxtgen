<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Poster;

use App\Data\Models\SatelliteLink;
use App\Domains\Card\Jobs\CreateQrCodeSvgJob;
use App\Domains\Church\DataObjects\PosterLinkData;
use App\Domains\Church\Jobs\GetSatelliteLinksJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Features\BaseChurchFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The printable QR poster (plan §6 R1; Roy 2026-11-06): the satellite's registration address as a QR code, then the
 * satellite's own Links (its Facebook group, its WhatsApp group…), each a QR code with its label. The satellite is
 * the one in the address (DECISIONS 2026-11-08); another is picked by switching in the bar, not `?satellite=`.
 * Codes are sent as images (data URIs), never as markup to inject.
 *
 * @extends BaseChurchFeature<Response>
 */
#[UserStory(
    title: 'Print the QR poster',
    persona: 'staff',
    happyPaths: [
        'story#80' => 'Staff get a poster whose QR code opens the church\'s registration form',
        'story#174' => 'The poster shows the satellite\'s Links after it, each with its QR code; someone in two satellites picks',
    ],
    unhappyPaths: [
        'story#81' => 'A Guardian with no Role in the Church gets no poster; the page is not found',
        'story#175' => 'Never a satellite the person is not assigned to',
    ],
)]
#[AccessRow(AccessRowEnum::StaffArea)]
final class ShowPosterFeature extends BaseChurchFeature
{
    public function handle(): Response
    {
        // The poster of the satellite in the address (DECISIONS 2026-11-08): its registration link and its own Links.
        // The QR carries the satellite's code, which never changes — a printed poster outlives an address change; the
        // address printed under it is the one to type (spec item 7).
        $satellite = $this->satellite();
        $url = route('public.register', $satellite);
        $qrUrl = route('public.register', $satellite->code);
        $links = $this->run(new GetSatelliteLinksJob(church: $this->church(), satelliteIds: [$satellite->id]))[$satellite->id] ?? [];

        return Inertia::render('Church/Poster', [
            'register_url' => $url,
            'qr_url' => $qrUrl,
            'qr' => $this->qr($qrUrl),
            'links' => array_map(fn (SatelliteLink $l): PosterLinkData => new PosterLinkData(label: $l->label, url: $l->url, qr: $this->qr($l->url)), $links),
        ]);
    }

    private function qr(string $text): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->run(new CreateQrCodeSvgJob(text: $text)));
    }
}
