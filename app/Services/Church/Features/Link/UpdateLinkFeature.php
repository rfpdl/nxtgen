<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Link;

use App\Data\Models\SatelliteLink;
use App\Domains\Church\Jobs\UpdateSatelliteLinkJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Data\SatelliteLinkInput;
use Illuminate\Http\RedirectResponse;

/**
 * Rename a Link or change its address; its place on the poster stays.
 *
 * @extends BaseLinkFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Change a Link',
    persona: 'church admin or satellite admin',
    happyPaths: ['story#168' => 'An Admin renames a Link or changes its address'],
    unhappyPaths: ['story#169' => 'Only a Link at a satellite they look after, in their church; only to an https address'],
)]
#[AccessRow(AccessRowEnum::ManageLinks)]
final class UpdateLinkFeature extends BaseLinkFeature
{
    public function __construct(private readonly SatelliteLink $link) {}

    public function handle(SatelliteLinkInput $input): RedirectResponse
    {
        $link = $this->scopedLink($this->link);

        $this->run(new UpdateSatelliteLinkJob(link: $link, label: $input->label, url: $input->url));

        return back()->with('success', "Saved \"{$input->label}\".");
    }
}
