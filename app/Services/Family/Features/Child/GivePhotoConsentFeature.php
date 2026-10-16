<?php

declare(strict_types=1);

namespace App\Services\Family\Features\Child;

use App\Domains\Family\Jobs\UpdateChildPhotoConsentJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Services\Family\Features\BaseFamilyFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * A parent agrees to a photo WITHOUT sending one — so a Lifeshaper can take the first photo at church (Roy
 * 2026-10-16; review 3 found this path missing).
 *
 * @extends BaseFamilyFeature<RedirectResponse>
 */
#[AccessRow(AccessRowEnum::OwnFamily)]
final class GivePhotoConsentFeature extends BaseFamilyFeature
{
    public function __construct(private readonly int $childId) {}

    public function handle(): RedirectResponse
    {
        $child = $this->ownChild($this->childId);
        $guardian = $this->guardianUser();

        // Consent and its history note together, or neither (review 4).
        DB::transaction(fn () => $this->run(new UpdateChildPhotoConsentJob(child: $child, given: true, by: $guardian)));

        return back()->with('success', "Thank you — you or a volunteer can now add {$child->displayName()}'s photo.");
    }
}
