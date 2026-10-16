<?php

declare(strict_types=1);

namespace App\Services\Family\Features\Photo;

use App\Data\Models\Child;
use App\Domains\Church\Jobs\GetAccessScopeJob;
use App\Domains\Family\Jobs\GetChildWithFamilyJob;
use App\Domains\Family\Jobs\GetGuardianForUserJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A child's photo, streamed from private storage (plan §3.3). The link is signed and short-lived, AND whoever
 * opens it must be a parent in the child's family or staff who can see the family — a forwarded link is
 * useless to anyone else. Never cached.
 *
 * In the Family service because the photo is the family's, shown only under its Guardian's consent; its own base
 * (BaseChildPhotoFeature) because staff open it too and RequireGuardian does not run on it.
 *
 * @extends BaseChildPhotoFeature<StreamedResponse>
 */
#[UserStory(
    title: 'See a child\'s photo',
    persona: 'parent or staff',
    happyPaths: ['story#40' => 'A photo opens by a short-lived signed link, for the Family and for Staff who can see the Family'],
    unhappyPaths: ['story#41' => 'No photo and no link once Consent is gone, or when there is no photo'],
)]
#[AccessRow(AccessRowEnum::SeeChildPhoto)]
final class ShowChildPhotoFeature extends BaseChildPhotoFeature
{
    public function __construct(private readonly int $childId) {}

    public function handle(): StreamedResponse
    {
        $user = $this->viewer();
        $child = $this->run(new GetChildWithFamilyJob(id: $this->childId));
        // No consent, no photo — even one already stored (e.g. an upload that raced a withdrawal).
        abort_unless($child instanceof Child && $child->photo_path !== null && $child->photo_consent_at !== null, 404);

        $church = $child->family->church;
        $mine = $this->run(new GetGuardianForUserJob(user: $user, church: $church))?->family_id === $child->family_id;
        abort_unless($mine || $this->run(new GetAccessScopeJob(user: $user, church: $church))->canSeeFamily($child->family), 404);

        return Storage::disk('photos')->response($child->photo_path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
