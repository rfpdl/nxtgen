<?php

declare(strict_types=1);

namespace App\Domains\Card\Jobs;

use App\Data\Models\CardRequest;
use App\Data\Models\Church;
use App\Foundation\Jobs\BaseJob;

/**
 * A Card request by id, ONLY within this church, with its child and the child's family (who may move it is decided
 * on the family).
 *
 * @extends BaseJob<CardRequest>
 */
final class GetCardRequestJob extends BaseJob
{
    public function __construct(
        private readonly Church $church,
        private readonly int $id,
    ) {}

    public function handle(): CardRequest
    {
        return CardRequest::query()->with('child.family')->where('church_id', $this->church->id)->findOrFail($this->id);
    }
}
