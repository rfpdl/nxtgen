<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Church;
use App\Foundation\Jobs\BaseJob;

/**
 * Re-read the church and LOCK its row for the rest of the caller's transaction: Moving-Up and putting it back run one
 * at a time per church, so a second press waits and then sees what the first did. The transaction is the caller's.
 *
 * @extends BaseJob<Church>
 */
final class GetChurchForUpdateJob extends BaseJob
{
    public function __construct(private readonly Church $church) {}

    public function handle(): Church
    {
        return Church::query()->whereKey($this->church->id)->lockForUpdate()->sole();
    }
}
