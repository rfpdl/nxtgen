<?php

declare(strict_types=1);

namespace App\Services\Family\Features\Child;

use App\Domains\Family\Jobs\DeleteChildPhotoJob;
use App\Domains\Family\Jobs\GetFamilyChildJob;
use App\Domains\Family\Jobs\UpdateChildPhotoConsentJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Services\Family\Features\BaseFamilyFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * A parent takes photo consent back: the photo is deleted, and no one can add another until they agree again.
 *
 * @extends BaseFamilyFeature<RedirectResponse>
 */
#[AccessRow(AccessRowEnum::OwnFamily)]
final class WithdrawPhotoConsentFeature extends BaseFamilyFeature
{
    public function __construct(private readonly int $childId) {}

    public function handle(): RedirectResponse
    {
        $child = $this->run(new GetFamilyChildJob(family: $this->family(), childId: $this->childId));

        DB::transaction(function () use ($child): void {
            $this->run(new DeleteChildPhotoJob(child: $child));
            $this->run(new UpdateChildPhotoConsentJob(child: $child, parent: null));
        });

        return back()->with('success', "Done — no photo of {$child->displayName()} is kept.");
    }
}
