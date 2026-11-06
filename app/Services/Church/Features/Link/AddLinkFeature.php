<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Link;

use App\Data\Models\SatelliteLink;
use App\Domains\Church\Jobs\CreateSatelliteLinkJob;
use App\Domains\Church\Jobs\GetSatelliteForUpdateJob;
use App\Domains\Church\Jobs\GetSatelliteJob;
use App\Domains\Church\Jobs\GetSatelliteLinksJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Data\AddSatelliteLinkInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Add a Link to a satellite's poster, last: a label and an https address. A satellite this admin does not look
 * after — or another church's — is a 404; a poster already holding SatelliteLink::MAX_PER_SATELLITE is refused, so
 * it still prints on one page. The satellite is locked while it counts and adds, so two admins at once never pass the
 * limit or share a place (Astra).
 *
 * @extends BaseLinkFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Add a Link',
    persona: 'church admin or satellite admin',
    happyPaths: ['story#166' => 'An Admin adds a Link — a label and an address — last on a satellite\'s poster'],
    unhappyPaths: ['story#167' => 'Only at a satellite they look after; a label and an https address are needed; at most 6 a satellite'],
)]
#[AccessRow(AccessRowEnum::ManageLinks)]
final class AddLinkFeature extends BaseLinkFeature
{
    public function handle(AddSatelliteLinkInput $input): RedirectResponse
    {
        $church = $this->church();
        $scope = $this->scope();
        abort_unless($scope->canManageLinks(), 404);

        $satellite = $this->run(new GetSatelliteJob(church: $church, id: $input->satellite_id));
        abort_unless($scope->canManageLinksAt($satellite), 404);

        DB::transaction(function () use ($church, $satellite, $input): void {
            $this->run(new GetSatelliteForUpdateJob(satellite: $satellite));
            $held = $this->run(new GetSatelliteLinksJob(church: $church, satelliteIds: [$satellite->id]))[$satellite->id] ?? [];
            if (count($held) >= SatelliteLink::MAX_PER_SATELLITE) {
                throw ValidationException::withMessages(['label' => "{$satellite->name}'s poster has ".SatelliteLink::MAX_PER_SATELLITE.' links already — remove one first.']);
            }

            $this->run(new CreateSatelliteLinkJob(satellite: $satellite, label: $input->label, url: $input->url));
        });

        return back()->with('success', "Added \"{$input->label}\" to {$satellite->name}'s poster.");
    }
}
