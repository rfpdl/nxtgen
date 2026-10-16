<?php

declare(strict_types=1);

namespace App\Domains\Registry\Jobs;

use App\Data\Models\Church;
use App\Data\Models\RegistryImportMapping;
use App\Foundation\Jobs\BaseJob;

/** @extends BaseJob<array<string, int>> source key => family id */
final class GetImportedSourceKeysJob extends BaseJob
{
    public function __construct(private readonly Church $church) {}

    /** @return array<string, int> */
    public function handle(): array
    {
        $map = [];
        foreach (RegistryImportMapping::query()->where('church_id', $this->church->id)->get() as $m) {
            $map[$m->source_child_key] = $m->family_id;
        }

        return $map;
    }
}
