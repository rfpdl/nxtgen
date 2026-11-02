<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Foundation\Jobs\BaseJob;

/**
 * Every stored photo this church's children point at, hidden ones included: taking consent back hides a photo but
 * keeps it (Roy 2026-10-16), so the photo sweep must keep it too.
 *
 * @extends BaseJob<list<string>>
 */
final class GetChildPhotoPathsJob extends BaseJob
{
    public function __construct(private readonly Church $church) {}

    /** @return list<string> */
    public function handle(): array
    {
        /** @var list<string> */
        return Child::query()
            ->where('church_id', $this->church->id)
            ->whereNotNull('photo_path')
            ->pluck('photo_path')
            ->all();
    }
}
