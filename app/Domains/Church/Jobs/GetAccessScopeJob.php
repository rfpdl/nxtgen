<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Church;
use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Domains\Church\DataObjects\AccessScope;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use App\Foundation\Jobs\BaseJob;

/** @extends BaseJob<AccessScope> */
final class GetAccessScopeJob extends BaseJob
{
    public function __construct(
        private readonly User $user,
        private readonly Church $church,
    ) {}

    public function handle(): AccessScope
    {
        $assignments = RoleAssignment::query()
            ->where('church_id', $this->church->id)
            ->where('user_id', $this->user->id)
            ->where('is_active', true)
            ->get();

        $ids = fn (RoleEnum $role, ScopeTypeEnum $type): array => array_values($assignments
            ->filter(fn (RoleAssignment $a): bool => $a->role === $role && $a->scope_type === $type)
            ->map(fn (RoleAssignment $a): int => $a->scope_id)
            ->all());

        return new AccessScope(
            churchId: $this->church->id,
            isChurchAdmin: $ids(RoleEnum::Admin, ScopeTypeEnum::Church) !== [],
            isChurchLifeshaper: $ids(RoleEnum::Lifeshaper, ScopeTypeEnum::Church) !== [],
            adminSatelliteIds: $ids(RoleEnum::Admin, ScopeTypeEnum::Satellite),
            lifeshaperSatelliteIds: $ids(RoleEnum::Lifeshaper, ScopeTypeEnum::Satellite),
            adminSeriesIds: $ids(RoleEnum::Admin, ScopeTypeEnum::EventSeries),
        );
    }
}
