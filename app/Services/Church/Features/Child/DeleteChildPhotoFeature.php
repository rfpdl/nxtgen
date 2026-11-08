<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Child;

use App\Data\Models\Child;
use App\Domains\Family\Jobs\DeleteChildPhotoJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * An admin deletes a child's photo for good (TODO #25) — for a parent who asks in person. It cannot be undone, so an
 * admin who may edit the family, in Admin; never a Lifeshaper. Noted on the child's history with the admin's name.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Delete a child\'s photo for good, for a parent',
    persona: 'church admin or satellite admin',
    happyPaths: ['story#178' => 'An Admin who may edit the Family deletes a child\'s photo for good, in Admin'],
    unhappyPaths: ['story#179' => 'Not a Lifeshaper, not another Satellite\'s Admin, not from Serve'],
)]
#[AccessRow(AccessRowEnum::DeleteChildPhoto)]
final class DeleteChildPhotoFeature extends BaseChurchFeature
{
    public function __construct(private readonly Child $child) {}

    public function handle(): RedirectResponse
    {
        $child = $this->scopedChild($this->child, $this->scope()->canEditFamily(...));
        $staff = $this->staff();

        return DB::transaction(fn (): bool => $this->run(new DeleteChildPhotoJob(child: $child, by: $staff)))
            ? back()->with('success', "{$child->displayName()}'s photo is deleted for good.")
            : back()->with('error', "{$child->displayName()} has no photo to delete.");
    }
}
