<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Child;

use App\Domains\Family\Jobs\UpdateChildPhotoConsentJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * A staff member records that the parent said yes to a photo IN PERSON (Roy 2026-10-16: "both can"). Saved with who
 * recorded it, and noted on the child's history so it can be checked later. Still no photo without consent.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Record a parent\'s yes to photos',
    persona: 'Lifeshaper',
    happyPaths: ['story#66' => 'A Lifeshaper records that the parent said yes in person, with who recorded it'],
    unhappyPaths: ['story#67' => 'Not for a Family they cannot see'],
)]
#[AccessRow(AccessRowEnum::ChildPhoto)]
final class RecordPhotoConsentFeature extends BaseChurchFeature
{
    public function __construct(private readonly int $childId) {}

    public function handle(): RedirectResponse
    {
        $child = $this->scopedChild($this->childId, $this->scope()->canSeeFamily(...));
        $staff = $this->staff();

        // Consent and its history note together, or neither (review 4).
        DB::transaction(fn () => $this->run(new UpdateChildPhotoConsentJob(child: $child, given: true, by: $staff, inPerson: true)));

        return back()->with('success', "Consent recorded. You can take {$child->displayName()}'s photo now.");
    }
}
