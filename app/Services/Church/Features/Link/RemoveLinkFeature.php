<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Link;

use App\Data\Models\SatelliteLink;
use App\Domains\Church\Jobs\DeleteSatelliteLinkJob;
use App\Domains\Church\Jobs\GetSatelliteForUpdateJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Remove a Link from its satellite's poster — with the satellite locked, as every change to its Links.
 *
 * @extends BaseLinkFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Remove a Link',
    persona: 'church admin or satellite admin',
    happyPaths: ['story#172' => 'An Admin removes a Link from the poster'],
    unhappyPaths: ['story#173' => 'Only a Link at a satellite they look after, in their church'],
)]
#[AccessRow(AccessRowEnum::ManageLinks)]
final class RemoveLinkFeature extends BaseLinkFeature
{
    public function __construct(private readonly SatelliteLink $link) {}

    public function handle(): RedirectResponse
    {
        $link = $this->scopedLink($this->link);

        DB::transaction(function () use ($link): void {
            $this->run(new GetSatelliteForUpdateJob(satellite: $link->satellite));
            $this->run(new DeleteSatelliteLinkJob(link: $link));
        });

        return back()->with('success', "Removed \"{$link->label}\" from {$link->satellite->name}'s poster.");
    }
}
