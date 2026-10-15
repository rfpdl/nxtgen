<?php

declare(strict_types=1);

namespace App\Services\Org\Features\Child;

use App\Domains\Family\Jobs\CreateChildPhotoJob;
use App\Domains\Family\Jobs\GetOrganizationChildJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Inputs\ChildPhotoInput;
use App\Services\Org\Features\BaseOrgFeature;
use Illuminate\Http\RedirectResponse;

/**
 * A Lifeshaper (any staff who can see the family) takes or uploads a child's photo — only once the PARENT has
 * agreed (Roy 2026-10-16). Staff can never agree for them: a `consent` in this request is ignored.
 *
 * @extends BaseOrgFeature<RedirectResponse>
 */
#[AccessRow(AccessRowEnum::ChildPhoto)]
final class StaffAddChildPhotoFeature extends BaseOrgFeature
{
    public function __construct(private readonly int $childId) {}

    public function handle(ChildPhotoInput $input): RedirectResponse
    {
        $child = $this->run(new GetOrganizationChildJob(organizationId: $this->organization()->id, childId: $this->childId));
        abort_unless($this->scope()->canSeeFamily($child->family), 404);

        if ($child->photo_consent_at === null) {
            return back()->with('error', 'The parent has not agreed to a photo yet. They can say yes on their family page.');
        }

        $this->run(new CreateChildPhotoJob(child: $child, file: $input->photo));

        return back()->with('success', "{$child->displayName()}'s photo is saved.");
    }
}
