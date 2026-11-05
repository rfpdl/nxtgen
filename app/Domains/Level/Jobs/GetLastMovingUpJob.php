<?php

declare(strict_types=1);

namespace App\Domains\Level\Jobs;

use App\Data\Models\Church;
use App\Data\Models\MovingUp;
use App\Foundation\Jobs\BaseJob;

/**
 * The church's latest Moving-Up — the only one that can be put back — with its children (and who ran it, who put any
 * back) for the page. None for a church that has never moved up.
 *
 * @extends BaseJob<MovingUp|null>
 */
final class GetLastMovingUpJob extends BaseJob
{
    public function __construct(private readonly Church $church) {}

    public function handle(): ?MovingUp
    {
        return MovingUp::query()
            ->where('church_id', $this->church->id)
            ->with(['maker', 'children' => fn ($q) => $q->where('church_id', $this->church->id)->orderBy('id'), 'children.child', 'children.putBackBy'])
            ->latest('id')
            ->first();
    }
}
