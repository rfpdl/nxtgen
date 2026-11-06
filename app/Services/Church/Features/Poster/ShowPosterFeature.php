<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Poster;

use App\Data\Models\SatelliteLink;
use App\Domains\Card\Jobs\CreateQrCodeSvgJob;
use App\Domains\Church\DataObjects\PosterLinkData;
use App\Domains\Church\DataObjects\SatelliteOptionData;
use App\Domains\Church\Jobs\GetSatelliteLinksJob;
use App\Domains\Church\Jobs\GetSatellitesJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Features\BaseChurchFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The printable QR poster (plan §6 R1; Roy 2026-11-06): this church's registration address as a QR code, then the
 * satellite's own Links (its Facebook group, its WhatsApp group…), each a QR code with its label. A satellite the
 * person is assigned to (`?satellite=`; church-wide Roles: any) — anything else falls back to the first of theirs.
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
        $church = $this->church();
        $url = route('public.register', $church->slug);

        // The satellites the person is assigned to (Roy: "assign you to satellite A, you can only do stuff in satellite A").
        $ids = $this->scope()->visibleSatelliteIds();
        $satellites = array_values(array_filter(
            $this->run(new GetSatellitesJob(church: $church)),
            fn (SatelliteOptionData $s): bool => $ids === null || in_array($s->id, $ids, true),
        ));
        $asked = request()->integer('satellite');
        $satellite = array_values(array_filter($satellites, fn (SatelliteOptionData $s): bool => $s->id === $asked))[0] ?? $satellites[0] ?? null;
        $links = $satellite === null ? [] : $this->run(new GetSatelliteLinksJob(church: $church, satelliteIds: [$satellite->id]))[$satellite->id] ?? [];

        return Inertia::render('Church/Poster', [
            'register_url' => $url,
            'qr' => $this->qr($url),
            'satellite' => $satellite,
            'satellites' => $satellites,
            'links' => array_map(fn (SatelliteLink $l): PosterLinkData => new PosterLinkData(label: $l->label, url: $l->url, qr: $this->qr($l->url)), $links),
        ]);
    }

    private function qr(string $text): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->run(new CreateQrCodeSvgJob(text: $text)));
    }
}
