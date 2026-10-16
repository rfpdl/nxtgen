<?php

declare(strict_types=1);

namespace App\Domains\Registry\Jobs;

use App\Data\Models\Child;
use App\Data\Models\RegistryImportMapping;
use App\Foundation\Jobs\BaseJob;

/** @extends BaseJob<RegistryImportMapping> */
final class CreateImportMappingJob extends BaseJob
{
    public function __construct(
        private readonly Child $child,
        private readonly string $sourceKey,
        private readonly string $familyKey,
    ) {}

    public function handle(): RegistryImportMapping
    {
        return RegistryImportMapping::query()->create([
            'church_id' => $this->child->church_id,
            'source_child_key' => $this->sourceKey,
            'approved_family_key' => $this->familyKey,
            'child_id' => $this->child->id,
            'family_id' => $this->child->family_id,
            'imported_at' => now(),
        ]);
    }
}
