<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Data\Models\Family;
use App\Foundation\Jobs\BaseJob;

/**
 * A child by id, ONLY within this family — a parent can never reach another family's child.
 *
 * @extends BaseJob<Child>
 */
final class GetFamilyChildJob extends BaseJob
{
    public function __construct(
        private readonly Family $family,
        private readonly int $id,
    ) {}

    public function handle(): Child
    {
        return Child::query()->where('family_id', $this->family->id)->findOrFail($this->id);
    }
}
