<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Link;

use App\Data\Models\SatelliteLink;
use App\Domains\Church\DataObjects\SatelliteLinkData;
use App\Domains\Church\DataObjects\SatelliteLinksData;
use App\Domains\Church\DataObjects\SatelliteOptionData;
use App\Domains\Church\Jobs\GetSatelliteLinksJob;
use App\Domains\Church\Jobs\GetSatellitesJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Links (GLOSSARY; Roy 2026-11-06): each satellite this admin looks after, with the Links its QR poster shows, in
 * order. A church admin sees every satellite; a satellite admin their own. Adding, changing, moving and removing
 * happen on this page.
 *
 * @extends BaseLinkFeature<Response>
 */
#[UserStory(
    title: 'See a satellite\'s Links',
    persona: 'church admin or satellite admin',
    happyPaths: ['story#164' => 'An Admin sees the Links of the satellites they look after, in the poster\'s order; a church admin every satellite\'s'],
    unhappyPaths: ['story#165' => 'Not there for a Lifeshaper'],
)]
#[AccessRow(AccessRowEnum::ManageLinks)]
final class ShowLinksFeature extends BaseLinkFeature
{
    public function handle(): Response
    {
        $church = $this->church();
        $scope = $this->scope();
        abort_unless($scope->canManageLinks(), 404);

        $ids = $scope->linkSatelliteIds();
        $options = array_values(array_filter(
            $this->run(new GetSatellitesJob(church: $church)),
            fn (SatelliteOptionData $s): bool => $ids === null || in_array($s->id, $ids, true),
        ));
        $links = $this->run(new GetSatelliteLinksJob(church: $church, satelliteIds: array_map(fn (SatelliteOptionData $s): int => $s->id, $options)));

        return Inertia::render('Church/Links/Index', [
            'satellites' => array_map(fn (SatelliteOptionData $s): SatelliteLinksData => new SatelliteLinksData(
                id: $s->id,
                name: $s->name,
                links: array_map(SatelliteLinkData::fromModel(...), $links[$s->id] ?? []),
            ), $options),
            'max' => SatelliteLink::MAX_PER_SATELLITE,
        ]);
    }
}
