<?php

declare(strict_types=1);

namespace App\Domains\Level\Jobs;

use App\Data\Models\Church;
use App\Data\Models\MovingUp;
use App\Foundation\Jobs\BaseJob;

/**
 * A Moving-Up by the id in an address (the `{movingUp}` route binding) — found only inside this church.
 *
 * @extends BaseJob<MovingUp>
 */
final class GetMovingUpJob extends BaseJob
{
    public function __construct(
        private readonly Church $church,
        private readonly int $id,
    ) {}

    public function handle(): MovingUp
    {
        return MovingUp::query()->where('church_id', $this->church->id)->findOrFail($this->id);
    }
}
