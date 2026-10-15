<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;

/**
 * Record a parent's photo consent — or, with no parent, clear it. Only a parent of the child's family may give
 * it; the caller checks that.
 *
 * @extends BaseJob<Child>
 */
final class UpdateChildPhotoConsentJob extends BaseJob
{
    public function __construct(
        private readonly Child $child,
        private readonly ?User $parent,
    ) {}

    public function handle(): Child
    {
        $this->child->forceFill([
            'photo_consent_at' => $this->parent !== null ? now() : null,
            'photo_consent_by' => $this->parent?->id,
        ])->save();

        return $this->child;
    }
}
