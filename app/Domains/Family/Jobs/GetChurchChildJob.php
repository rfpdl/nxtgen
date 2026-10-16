<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Foundation\Jobs\BaseJob;

/**
 * A child by id, ONLY within this church.
 *
 * @extends BaseJob<Child>
 */
final class GetChurchChildJob extends BaseJob
{
    public function __construct(
        private readonly Church $church,
        private readonly int $id,
    ) {}

    public function handle(): Child
    {
        return Child::query()->with('family')->where('church_id', $this->church->id)->findOrFail($this->id);
    }
}
