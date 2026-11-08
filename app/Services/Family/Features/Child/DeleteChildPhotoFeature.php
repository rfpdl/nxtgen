<?php

declare(strict_types=1);

namespace App\Services\Family\Features\Child;

use App\Data\Models\Child;
use App\Domains\Family\Jobs\DeleteChildPhotoJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Family\Features\BaseFamilyFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * A parent deletes their child's photo for good (TODO #25): taking consent back only hides it; this removes it — a
 * hidden one too. Noted on the child's history. Their own child only.
 *
 * @extends BaseFamilyFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Delete a child\'s photo for good',
    persona: 'parent',
    happyPaths: ['story#176' => 'A parent deletes their child\'s photo for good — shown or hidden — and it is noted'],
    unhappyPaths: ['story#177' => 'Only their own child\'s; with no photo on file it says so'],
)]
#[AccessRow(AccessRowEnum::OwnFamily)]
final class DeleteChildPhotoFeature extends BaseFamilyFeature
{
    public function __construct(private readonly Child $child) {}

    public function handle(): RedirectResponse
    {
        $child = $this->ownChild($this->child);
        $guardian = $this->guardianUser();

        return DB::transaction(fn (): bool => $this->run(new DeleteChildPhotoJob(child: $child, by: $guardian)))
            ? back()->with('success', "{$child->displayName()}'s photo is deleted for good.")
            : back()->with('error', "{$child->displayName()} has no photo to delete.");
    }
}
