<?php

declare(strict_types=1);

namespace App\Services\Auth\Features\Photo;

use App\Data\Models\Child;
use App\Data\Models\User;
use App\Domains\Family\Jobs\GetChildWithFamilyJob;
use App\Domains\Family\Jobs\GetGuardianForUserJob;
use App\Domains\Organization\Jobs\GetAccessScopeJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Services\Auth\Features\BaseAuthFeature;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A child's photo, streamed from private storage (plan §3.3). The link is signed and short-lived, AND whoever
 * opens it must be a parent in the child's family or staff who can see the family — a forwarded link is
 * useless to anyone else. Never cached.
 *
 * @extends BaseAuthFeature<StreamedResponse>
 */
#[AccessRow(AccessRowEnum::SeeChildPhoto)]
final class ShowChildPhotoFeature extends BaseAuthFeature
{
    public function __construct(private readonly int $childId) {}

    public function handle(): StreamedResponse
    {
        $user = request()->user();
        abort_unless($user instanceof User, 404);
        $child = $this->run(new GetChildWithFamilyJob(childId: $this->childId));
        // No consent, no photo — even one already stored (e.g. an upload that raced a withdrawal).
        abort_unless($child instanceof Child && $child->photo_path !== null && $child->photo_consent_at !== null, 404);

        $church = $child->family->organization;
        $mine = $this->run(new GetGuardianForUserJob(user: $user, organization: $church))?->family_id === $child->family_id;
        abort_unless($mine || $this->run(new GetAccessScopeJob(user: $user, organization: $church))->canSeeFamily($child->family), 404);

        return Storage::disk('photos')->response($child->photo_path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
