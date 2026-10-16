<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Foundation\Jobs\BaseJob;

/**
 * A child by id, with its family and church — for a photo link, which carries only the child's id. The church
 * is READ from the child; the caller must then check the viewer against that church.
 *
 * @extends BaseJob<Child|null>
 */
final class GetChildWithFamilyJob extends BaseJob
{
    public function __construct(private readonly int $childId) {}

    public function handle(): ?Child
    {
        return Child::query()->with(['family.church'])->find($this->childId);
    }
}
