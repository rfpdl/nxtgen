<?php

declare(strict_types=1);

namespace App\Domains\Card\Jobs;

use App\Data\Models\Church;
use App\Data\Models\PrintList;
use App\Foundation\Jobs\BaseJob;

/**
 * A Print list by id, ONLY within this church.
 *
 * @extends BaseJob<PrintList>
 */
final class GetPrintListJob extends BaseJob
{
    public function __construct(
        private readonly Church $church,
        private readonly int $id,
    ) {}

    public function handle(): PrintList
    {
        return PrintList::query()->where('church_id', $this->church->id)->findOrFail($this->id);
    }
}
