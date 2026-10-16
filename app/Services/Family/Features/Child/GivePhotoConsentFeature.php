<?php

declare(strict_types=1);

namespace App\Services\Family\Features\Child;

use App\Data\Models\User;
use App\Domains\Family\Jobs\GetFamilyChildJob;
use App\Domains\Family\Jobs\UpdateChildPhotoConsentJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Services\Family\Features\BaseFamilyFeature;
use Illuminate\Http\RedirectResponse;

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
        $child = $this->run(new GetFamilyChildJob(family: $this->family(), childId: $this->childId));
        $parent = request()->user();
        abort_unless($parent instanceof User, 404);

        $this->run(new UpdateChildPhotoConsentJob(child: $child, parent: $parent));

        return back()->with('success', "Thank you — you or a volunteer can now add {$child->displayName()}'s photo.");
    }
}
