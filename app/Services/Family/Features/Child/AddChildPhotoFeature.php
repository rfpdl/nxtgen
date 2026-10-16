<?php

declare(strict_types=1);

namespace App\Services\Family\Features\Child;

use App\Domains\Family\Jobs\CreateChildPhotoJob;
use App\Domains\Family\Jobs\UpdateChildPhotoConsentJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Foundation\Inputs\ChildPhotoInput;
use App\Services\Family\Features\BaseFamilyFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A parent adds their child's photo (Roy 2026-10-16: take or upload). Agreeing and adding can happen together;
 * without consent on record or in this request, nothing is stored.
 *
 * @extends BaseFamilyFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Add my child\'s photo',
    persona: 'parent',
    happyPaths: ['story#38' => 'A parent adds a photo, agreeing in the same step'],
    unhappyPaths: ['story#39' => 'No photo without Consent, and none for another Family\'s Child'],
)]
#[AccessRow(AccessRowEnum::OwnFamily)]
final class AddChildPhotoFeature extends BaseFamilyFeature
{
    public function __construct(private readonly int $childId) {}

    public function handle(ChildPhotoInput $input): RedirectResponse
    {
        $child = $this->ownChild($this->childId);
        $guardian = $this->guardianUser();

        if ($child->photo_consent_at === null && ! $input->consent) {
            throw ValidationException::withMessages(['consent' => 'Please agree to the photo first.']);
        }

        $kept = DB::transaction(function () use ($child, $guardian, $input): bool {
            if ($child->photo_consent_at === null) {
                $this->run(new UpdateChildPhotoConsentJob(child: $child, given: true, by: $guardian));
            }

            return $this->run(new CreateChildPhotoJob(child: $child, file: $input->photo));
        });
        if (! $kept) {
            return back()->with('error', 'Consent was taken back while the photo was sending, so it was not kept.');
        }

        return back()->with('success', "{$child->displayName()}'s photo is saved.");
    }
}
