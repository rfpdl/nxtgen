<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Family;
use App\Foundation\Jobs\BaseJob;

/**
 * The family as it is now, its row locked until the transaction ends — so a check on which satellite it belongs to
 * (putting a Moving-Up back) holds while the change is made, and a move to another satellite waits for it.
 *
 * @extends BaseJob<Family>
 */
final class GetFamilyForUpdateJob extends BaseJob
{
    public function __construct(private readonly Family $family) {}

    public function handle(): Family
    {
        return Family::query()->where('church_id', $this->family->church_id)->whereKey($this->family->id)->lockForUpdate()->sole();
    }
}
