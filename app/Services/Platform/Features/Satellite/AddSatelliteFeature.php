<?php

declare(strict_types=1);

namespace App\Services\Platform\Features\Satellite;

use App\Domains\Church\Jobs\CreateSatelliteJob;
use App\Foundation\Attributes\UserStory;
use App\Services\Platform\Data\SatelliteInput;
use App\Services\Platform\Features\BasePlatformFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Add a satellite (spec item 12): its name and address; its QR code and Squads are made with it. It has no admin yet
 * — the platform admin gives the first on People.
 *
 * @extends BasePlatformFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Add a satellite',
    persona: 'platform admin',
    happyPaths: ['story#192' => 'The platform admin adds a Satellite: its name and address, a QR code that never changes, its Squads'],
    unhappyPaths: ['story#193' => 'An address that is taken, used by the app, shaped like a QR code or not plain is refused'],
)]
final class AddSatelliteFeature extends BasePlatformFeature
{
    public function handle(SatelliteInput $input): RedirectResponse
    {
        $satellite = DB::transaction(fn () => $this->run(new CreateSatelliteJob(church: $this->church(), name: $input->name, slug: $input->slug)));

        return redirect()->route('platform.satellites.index')
            ->with('success', "Added {$satellite->name} at /{$satellite->slug}. Give it its first admin on People.");
    }
}
