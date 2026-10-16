<?php

declare(strict_types=1);

namespace App\Services\Family\Features\Home;

use App\Domains\Church\Jobs\GetSatellitesJob;
use App\Domains\Family\DataObjects\FamilyDetailsData;
use App\Domains\Family\DataObjects\GuardianEditData;
use App\Domains\Family\Jobs\GetFamilyPortalJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Foundation\Support\FamilyFormEnums;
use App\Services\Family\Features\BaseFamilyFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The parent's own family page: the family, every child's details including health (plan §3.3 — the child's
 * own family sees them), and what the edit forms need: the family's details and the parent's OWN details.
 *
 * @extends BaseFamilyFeature<Response>
 */
#[UserStory(
    title: 'See my family',
    persona: 'parent',
    happyPaths: ['story#28' => 'A parent sees their own Family, Satellite and Children'],
    unhappyPaths: ['story#29' => 'Another church\'s family page, or one with no Family, is not found'],
)]
#[AccessRow(AccessRowEnum::OwnFamily)]
final class ShowFamilyFeature extends BaseFamilyFeature
{
    public function handle(): Response
    {
        $family = $this->family();
        $me = $this->guardian();

        return Inertia::render('Family/Show', [
            'family' => $this->run(new GetFamilyPortalJob(family: $family)),
            'details' => FamilyDetailsData::fromModel($family),
            'me' => GuardianEditData::fromModel($me),
            'satellites' => $this->run(new GetSatellitesJob(church: $family->church)),
            'enums' => FamilyFormEnums::toFrontend(),
        ]);
    }
}
