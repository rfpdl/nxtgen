<?php

declare(strict_types=1);

namespace App\Services\Public\Features\Registration;

use App\Data\Models\Satellite;
use App\Domains\Church\Jobs\GetSatellitesJob;
use App\Foundation\Attributes\UserStory;
use App\Foundation\Support\FamilyFormEnums;
use App\Services\Public\Features\BasePublicFeature;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The registration form at a satellite's address: the church's satellites to choose from, starting on the one in the
 * address (the shared `satellite` prop).
 *
 * A QR poster's code at the address forwards to the satellite's address (spec item 7).
 *
 * @extends BasePublicFeature<Response|RedirectResponse>
 */
#[UserStory(
    title: 'Open the registration form',
    persona: 'parent',
    happyPaths: ['story#2' => 'The form offers the church\'s Satellites; a QR poster\'s code forwards to the Satellite\'s address, whatever it is now'],
    unhappyPaths: ['story#3' => 'A satellite that does not exist is not found'],
)]
final class ShowRegisterFeature extends BasePublicFeature
{
    public function __construct(private readonly Satellite $satellite) {}

    public function handle(): Response|RedirectResponse
    {
        // A QR poster's code (spec item 7): on to the satellite's own address, whatever it is now.
        // What the address said, before binding: a code, not this satellite's address. The QR's query goes along.
        if (request()->route()?->originalParameter('satellite') !== $this->satellite->slug) {
            return redirect()->route('public.register', [$this->satellite, ...request()->query()]);
        }

        return Inertia::render('Public/Register', [
            'satellites' => $this->run(new GetSatellitesJob(church: $this->satellite->church)),
            'enums' => FamilyFormEnums::toFrontend(),
        ]);
    }
}
