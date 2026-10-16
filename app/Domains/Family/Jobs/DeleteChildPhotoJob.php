<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Foundation\Jobs\BaseJob;
use Illuminate\Support\Facades\Storage;

/**
 * Remove a child's photo: every stored file of the child AND the reference. Used when the parent takes consent back.
 *
 * @extends BaseJob<Child>
 */
final class DeleteChildPhotoJob extends BaseJob
{
    public function __construct(private readonly Child $child) {}

    public function handle(): Child
    {
        // Locked, so an upload finishing at the same moment waits and then sees consent gone (CreateChildPhotoJob).
        $locked = Child::query()->where('organization_id', $this->child->organization_id)->lockForUpdate()->findOrFail($this->child->id);
        // EVERY stored file of this child, not only the current one: a save that failed half-way can leave an
        // orphan, and consent withdrawn must mean no photo kept anywhere (review 3).
        $disk = Storage::disk('photos');
        $mine = array_values(array_filter(
            $disk->files("children/{$locked->organization_id}"),
            fn (mixed $f): bool => is_string($f) && str_starts_with(basename($f), "{$locked->id}-"),
        ));
        $disk->delete($mine);
        if ($locked->photo_path !== null) {
            $locked->forceFill(['photo_path' => null])->save();
        }
        $this->child->setRawAttributes($locked->getAttributes(), true);

        return $this->child;
    }
}
