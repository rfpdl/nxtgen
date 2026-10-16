<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Family;
use App\Domains\Family\DataObjects\FamilyPortalData;
use App\Foundation\Jobs\BaseJob;

/** @extends BaseJob<FamilyPortalData> */
final class GetFamilyPortalJob extends BaseJob
{
    public function __construct(private readonly Family $family) {}

    public function handle(): FamilyPortalData
    {
        $this->family->loadMissing(['homeSatellite', 'guardians', 'children.group']);

        return FamilyPortalData::fromModel($this->family);
    }
}
