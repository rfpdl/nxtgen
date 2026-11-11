<?php

declare(strict_types=1);

namespace App\Services\Platform\Features\Satellite;

use App\Data\Models\Satellite;
use App\Domains\Church\DataObjects\PlatformSatelliteData;
use App\Domains\Church\Jobs\GetSatelliteAdminCountsJob;
use App\Domains\Church\Jobs\GetSatellitesForChurchJob;
use App\Foundation\Attributes\UserStory;
use App\Services\Platform\DataObjects\PlatformSatellitesPageData;
use App\Services\Platform\Features\BasePlatformFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The platform's satellites (spec item 12): each with its address, its registration code and how many admins it has —
 * to add one, rename one or change its address, and give a new one its first admin.
 *
 * @extends BasePlatformFeature<Response>
 */
#[UserStory(
    title: 'See the satellites',
    persona: 'platform admin',
    happyPaths: ['story#190' => 'The platform admin sees every satellite: its address, its code and how many admins it has'],
    unhappyPaths: ['story#191' => 'Nobody but the platform admin opens /platform'],
)]
final class ShowSatellitesFeature extends BasePlatformFeature
{
    public function handle(): Response
    {
        $church = $this->church();
        $admins = $this->run(new GetSatelliteAdminCountsJob(church: $church));

        return Inertia::render('Platform/Satellites', new PlatformSatellitesPageData(
            satellites: array_map(
                fn (Satellite $s): PlatformSatelliteData => PlatformSatelliteData::fromModel($s, $admins[$s->id] ?? 0),
                $this->run(new GetSatellitesForChurchJob(church: $church)),
            ),
        ));
    }
}
