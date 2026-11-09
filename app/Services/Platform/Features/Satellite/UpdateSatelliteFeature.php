<?php

declare(strict_types=1);

namespace App\Services\Platform\Features\Satellite;

use App\Data\Models\Satellite;
use App\Domains\Church\Jobs\UpdateSatelliteJob;
use App\Foundation\Attributes\UserStory;
use App\Services\Platform\Data\SatelliteInput;
use App\Services\Platform\Features\BasePlatformFeature;
use Illuminate\Http\RedirectResponse;

/**
 * Rename a satellite or move its address (spec item 12). Its QR code stays, so printed posters still find it at its
 * new address; the old address is nobody's.
 *
 * @extends BasePlatformFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Rename a satellite or change its address',
    persona: 'platform admin',
    happyPaths: ['story#194' => 'The platform admin renames a Satellite or moves its address; its printed QR code still finds it'],
    unhappyPaths: ['story#195' => 'Another Satellite\'s address, or one shaped like a QR code, is refused'],
)]
final class UpdateSatelliteFeature extends BasePlatformFeature
{
    public function __construct(private readonly Satellite $satellite) {}

    public function handle(SatelliteInput $input): RedirectResponse
    {
        abort_unless($this->satellite->church_id === $this->church()->id, 404);
        $satellite = $this->run(new UpdateSatelliteJob(satellite: $this->satellite, name: $input->name, slug: $input->slug));

        return redirect()->route('platform.satellites.index')->with('success', "{$satellite->name} is at /{$satellite->slug}.");
    }
}
