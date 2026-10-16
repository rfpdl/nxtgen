<?php

declare(strict_types=1);

namespace App\Services\Org\Features\Child;

use App\Domains\Family\Jobs\CreateSystemNoteJob;
use App\Domains\Family\Jobs\GetOrganizationChildJob;
use App\Domains\Family\Jobs\UpdateChildPhotoConsentJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Services\Org\Features\BaseOrgFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * A volunteer records that the parent said yes to a photo IN PERSON (Roy 2026-10-16: "both can"). Saved with who
 * recorded it, and noted on the child's history so it can be checked later. Still no photo without consent.
 *
 * @extends BaseOrgFeature<RedirectResponse>
 */
#[AccessRow(AccessRowEnum::ChildPhoto)]
final class RecordPhotoConsentFeature extends BaseOrgFeature
{
    public function __construct(private readonly int $childId) {}

    public function handle(): RedirectResponse
    {
        $child = $this->run(new GetOrganizationChildJob(organizationId: $this->organization()->id, childId: $this->childId));
        abort_unless($this->scope()->canSeeFamily($child->family), 404);
        $staff = $this->staff();

        DB::transaction(function () use ($child, $staff): void {
            $this->run(new UpdateChildPhotoConsentJob(child: $child, by: $staff));
            $this->run(new CreateSystemNoteJob(
                about: $child,
                body: "{$child->displayName()}: photo consent recorded by {$staff->name} — the parent said yes in person.",
                by: $staff,
            ));
        });

        return back()->with('success', "Consent recorded. You can take {$child->displayName()}'s photo now.");
    }
}
