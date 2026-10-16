<?php

declare(strict_types=1);

namespace App\Services\Public\Features\Registration;

use App\Data\Models\Church;
use App\Domains\Church\Jobs\GetSatellitesJob;
use App\Foundation\Attributes\UserStory;
use App\Foundation\Support\FamilyFormEnums;
use App\Services\Public\Features\BasePublicFeature;
use Inertia\Inertia;
use Inertia\Response;

/** @extends BasePublicFeature<Response> */
#[UserStory(
    title: 'Open the registration form',
    persona: 'parent',
    happyPaths: ['story#2' => 'The form offers the church\'s Satellites'],
    unhappyPaths: ['story#3' => 'A church that does not exist is not found'],
)]
final class ShowRegisterFeature extends BasePublicFeature
{
    public function __construct(private readonly Church $church) {}

    public function handle(): Response
    {
        return Inertia::render('Public/Register', [
            'satellites' => $this->run(new GetSatellitesJob(church: $this->church)),
            'enums' => FamilyFormEnums::toFrontend(),
        ]);
    }
}
