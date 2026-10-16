<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;

/**
 * Record photo consent — or, with nobody, clear it. `$by` is who recorded it: the parent on their family page, or
 * a volunteer the parent told in person (Roy 2026-10-16). The caller checks who may.
 *
 * @extends BaseJob<Child>
 */
final class UpdateChildPhotoConsentJob extends BaseJob
{
    public function __construct(
        private readonly Child $child,
        private readonly ?User $by,
    ) {}

    public function handle(): Child
    {
        $this->child->forceFill([
            'photo_consent_at' => $this->by !== null ? now() : null,
            'photo_consent_by' => $this->by?->id,
        ])->save();

        return $this->child;
    }
}
