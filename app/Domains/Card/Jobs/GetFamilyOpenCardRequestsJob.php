<?php

declare(strict_types=1);

namespace App\Domains\Card\Jobs;

use App\Data\Models\CardRequest;
use App\Data\Models\Family;
use App\Foundation\Jobs\BaseJob;

/**
 * A family's children's OPEN Card requests (Asked, Approved or Printed), for the staff family page (name cards issue
 * 01): at most one per child, in the order filed (by id). A Given or Refused request is closed and left out.
 *
 * @extends BaseJob<list<CardRequest>>
 */
final class GetFamilyOpenCardRequestsJob extends BaseJob
{
    public function __construct(private readonly Family $family) {}

    /** @return list<CardRequest> */
    public function handle(): array
    {
        return array_values(CardRequest::query()
            ->where('church_id', $this->family->church_id)
            ->whereIn('child_id', $this->family->childIds())
            ->whereNotNull('open_child_id')
            ->orderBy('id')
            ->get()
            ->all());
    }
}
