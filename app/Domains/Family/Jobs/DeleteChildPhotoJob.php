<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Foundation\Jobs\BaseJob;
use Illuminate\Support\Facades\Storage;

/**
 * Remove a child's photo: the file AND the reference. Used when the parent takes consent back.
 *
 * @extends BaseJob<Child>
 */
final class DeleteChildPhotoJob extends BaseJob
{
    public function __construct(private readonly Child $child) {}

    public function handle(): Child
    {
        if ($this->child->photo_path !== null) {
            Storage::disk('photos')->delete($this->child->photo_path);
            $this->child->forceFill(['photo_path' => null])->save();
        }

        return $this->child;
    }
}
