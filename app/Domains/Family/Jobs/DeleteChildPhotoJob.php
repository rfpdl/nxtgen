<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Data\Models\Note;
use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Delete a child's photo for good (TODO #25, SECURITY #19): the child stops pointing at it and the child's history
 * says who did it — then, once that is saved, the stored file is deleted. Consent is left as it was. The child is
 * LOCKED first, as an upload locks it (CreateChildPhotoJob), so the two run one after the other; the caller runs this
 * in a transaction. A save that fails keeps the file; a file that will not delete points at nothing and the nightly
 * sweep (#22) removes it (Astra). Returns whether there was a photo.
 *
 * @extends BaseJob<bool>
 */
final class DeleteChildPhotoJob extends BaseJob
{
    public function __construct(
        private readonly Child $child,
        private readonly User $by,
    ) {}

    public function handle(): bool
    {
        $locked = Child::query()->where('church_id', $this->child->church_id)->lockForUpdate()->findOrFail($this->child->id);
        $path = $locked->photo_path;
        if ($path === null) {
            return false;
        }

        $locked->forceFill(['photo_path' => null])->save();
        $this->child->setRawAttributes($locked->getAttributes(), true);
        Note::system(about: $this->child, body: "{$this->child->displayName()}: photo deleted for good by {$this->by->name}.", by: $this->by);
        DB::afterCommit(function () use ($path): void {
            try {
                Storage::disk('photos')->delete($path);
            } catch (Throwable $e) {
                report($e);
            }
        });

        return true;
    }
}
