<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Child;

use App\Domains\Family\Jobs\CreateChildPhotoJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Foundation\Inputs\ChildPhotoInput;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * A Lifeshaper (any staff who can see the family) takes or uploads a child's photo — only once consent is
 * recorded (Roy 2026-10-16), by the parent or by a staff member the parent told (RecordPhotoConsentFeature). A
 * `consent` sent WITH the photo is ignored: consent is its own, noted step.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Take a child\'s photo',
    persona: 'Lifeshaper',
    happyPaths: ['story#68' => 'A Lifeshaper takes a photo once the parent has agreed'],
    unhappyPaths: ['story#69' => 'No photo before Consent, or for a Family they cannot see'],
)]
#[AccessRow(AccessRowEnum::ChildPhoto)]
final class StaffAddChildPhotoFeature extends BaseChurchFeature
{
    public function __construct(private readonly int $childId) {}

    public function handle(ChildPhotoInput $input): RedirectResponse
    {
        $child = $this->scopedChild($this->childId, $this->scope()->canSeeFamily(...));

        if ($child->photo_consent_at === null) {
            return back()->with('error', 'The parent has not agreed to a photo yet. If they say yes to you, record it first.');
        }

        $kept = DB::transaction(fn (): bool => $this->run(new CreateChildPhotoJob(child: $child, file: $input->photo)));
        if (! $kept) {
            return back()->with('error', 'The parent has just taken back their consent, so the photo was not kept.');
        }

        return back()->with('success', "{$child->displayName()}'s photo is saved.");
    }
}
