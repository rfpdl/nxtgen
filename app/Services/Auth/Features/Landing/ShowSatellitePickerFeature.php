<?php

declare(strict_types=1);

namespace App\Services\Auth\Features\Landing;

use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Domains\Church\DataObjects\SatelliteChoiceData;
use App\Domains\Church\Jobs\GetOpenSatellitesJob;
use App\Foundation\Attributes\UserStory;
use App\Services\Auth\DataObjects\PickSatellitePageData;
use App\Services\Auth\Features\BaseAuthFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Pick your satellite" (spec item 11): where someone who serves at several satellites lands when they used none yet
 * (or the last one is no longer theirs). Each opens its home there — Admin for an admin, else Serve. Not found for
 * anyone who serves nowhere.
 *
 * @extends BaseAuthFeature<Response>
 */
#[UserStory(
    title: 'Pick a satellite',
    persona: 'staff',
    happyPaths: ['story#186' => 'Someone who serves at several Satellites picks one; each opens its home there'],
    unhappyPaths: ['story#187' => 'Not there for someone who serves nowhere'],
)]
final class ShowSatellitePickerFeature extends BaseAuthFeature
{
    public function handle(): Response
    {
        $user = request()->user();
        abort_unless($user instanceof User, 404);

        $satellites = $this->run(new GetOpenSatellitesJob(user: $user));
        abort_if($satellites === [], 404);

        return Inertia::render('Auth/PickSatellite', new PickSatellitePageData(
            satellites: array_map(fn (Satellite $s): SatelliteChoiceData => SatelliteChoiceData::fromModel($s), $satellites),
        ));
    }
}
