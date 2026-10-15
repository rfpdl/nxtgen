<?php

declare(strict_types=1);

namespace App\Services\Family\Features\Home;

use App\Data\Models\User;
use App\Domains\Family\DataObjects\FamilyDetailsData;
use App\Domains\Family\DataObjects\GuardianEditData;
use App\Domains\Family\Jobs\GetFamilyPortalJob;
use App\Domains\Family\Jobs\GetGuardianForUserJob;
use App\Domains\Organization\Jobs\GetBranchesJob;
use App\Enums\AccessRowEnum;
use App\Enums\GenderEnum;
use App\Enums\RelationshipEnum;
use App\Foundation\Attributes\AccessRow;
use App\Services\Family\Features\BaseFamilyFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The parent's own family page: the family, every child's details including health (plan §3.3 — the child's
 * own family sees them), and what the edit forms need: the family's details and the parent's OWN details.
 *
 * @extends BaseFamilyFeature<Response>
 */
#[AccessRow(AccessRowEnum::OwnFamily)]
final class ShowFamilyFeature extends BaseFamilyFeature
{
    public function handle(): Response
    {
        $family = $this->family();
        $user = request()->user();
        abort_unless($user instanceof User, 404);
        $me = $this->run(new GetGuardianForUserJob(user: $user, organization: $family->organization));
        abort_if($me === null, 404);

        return Inertia::render('Family/Show', [
            'church' => ['name' => $family->organization->name, 'slug' => $family->organization->slug],
            'family' => $this->run(new GetFamilyPortalJob(family: $family)),
            'details' => FamilyDetailsData::fromModel($family),
            'me' => GuardianEditData::fromModel($me),
            'satellites' => $this->run(new GetBranchesJob(organization: $family->organization)),
            'relationships' => RelationshipEnum::toFrontend(),
            'genders' => GenderEnum::toFrontend(),
        ]);
    }
}
