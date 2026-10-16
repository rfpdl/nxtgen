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
 * A parent takes photo consent back: the photo is HIDDEN from everyone — no link, and the photo route refuses —
 * but kept, so it comes back if they agree again (Roy 2026-10-16). Nobody can add a photo meanwhile.
 *
 * @extends BaseFamilyFeature<RedirectResponse>
 */
#[AccessRow(AccessRowEnum::OwnFamily)]
final class WithdrawPhotoConsentFeature extends BaseFamilyFeature
{
    public function __construct(private readonly int $childId) {}

    public function handle(): RedirectResponse
    {
        $child = $this->ownChild($this->childId);
        $guardian = $this->guardianUser();

        DB::transaction(fn () => $this->run(new UpdateChildPhotoConsentJob(child: $child, given: false, by: $guardian)));

        return back()->with('success', "Done — {$child->displayName()}'s photo is hidden. If you say yes again, it comes back.");
    }
}
