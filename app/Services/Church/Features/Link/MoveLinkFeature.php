<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Link;

use App\Data\Models\SatelliteLink;
use App\Domains\Church\Jobs\GetSatelliteForUpdateJob;
use App\Domains\Church\Jobs\GetSatelliteLinkJob;
use App\Domains\Church\Jobs\UpdateSatelliteLinkPositionJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Data\MoveSatelliteLinkInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Move a Link one place up or down its satellite's poster. Quiet: the list shows the new order. At either end
 * nothing moves. The satellite is locked and the Link read again first, so two moves at once swap from where each
 * Link is NOW — never two Links in one place (Astra).
 *
 * @extends BaseLinkFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Move a Link',
    persona: 'satellite admin',
    happyPaths: ['story#170' => 'An Admin moves a Link up or down the poster'],
    unhappyPaths: ['story#171' => 'Only a Link at a satellite they look after; only up or down'],
)]
#[AccessRow(AccessRowEnum::ManageLinks)]
final class MoveLinkFeature extends BaseLinkFeature
{
    public function __construct(private readonly SatelliteLink $link) {}

    public function handle(MoveSatelliteLinkInput $input): RedirectResponse
    {
        $link = $this->scopedLink($this->link);

        DB::transaction(function () use ($link, $input): void {
            $this->run(new GetSatelliteForUpdateJob(satellite: $link->satellite));
            $now = $this->run(new GetSatelliteLinkJob(church: $this->church(), id: $link->id));
            $this->run(new UpdateSatelliteLinkPositionJob(link: $now, up: $input->direction === 'up'));
        });

        return back();
    }
}
