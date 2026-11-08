<?php

declare(strict_types=1);

namespace App\Services\Public\Features\Registration;

use App\Data\Models\Satellite;
use App\Domains\Church\Jobs\GetSatellitesJob;
use App\Foundation\Attributes\UserStory;
use App\Foundation\Support\FamilyFormEnums;
use App\Services\Public\Features\BasePublicFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The registration form at a satellite's address: the church's satellites to choose from, starting on the one in the
 * address (the shared `satellite` prop).
 *
 * @extends BasePublicFeature<Response>
 */
#[UserStory(
    title: 'Open the registration form',
    persona: 'parent',
    happyPaths: ['story#2' => 'The form offers the church\'s Satellites'],
    unhappyPaths: ['story#3' => 'A satellite that does not exist is not found'],
)]
final class ShowRegisterFeature extends BasePublicFeature
{
    public function __construct(private readonly Satellite $satellite) {}

    public function handle(): Response
    {
        return Inertia::render('Public/Register', [
            'satellites' => $this->run(new GetSatellitesJob(church: $this->satellite->church)),
            'enums' => FamilyFormEnums::toFrontend(),
        ]);
    }
}
