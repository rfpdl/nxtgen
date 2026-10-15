<?php

declare(strict_types=1);

namespace App\Domains\Organization\Jobs;

use App\Data\Models\Organization;
use App\Data\Models\RoleAssignment;
use App\Data\Models\User;
use App\Domains\Organization\DataObjects\AccessScope;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use App\Foundation\Jobs\BaseJob;

/** @extends BaseJob<AccessScope> */
final class GetAccessScopeJob extends BaseJob
{
    public function __construct(
        private readonly User $user,
        private readonly Organization $organization,
    ) {}

    public function handle(): AccessScope
    {
        $assignments = RoleAssignment::query()
            ->where('organization_id', $this->organization->id)
            ->where('user_id', $this->user->id)
            ->where('is_active', true)
            ->get();

        $ids = fn (RoleEnum $role, ScopeTypeEnum $type): array => array_values($assignments
            ->filter(fn (RoleAssignment $a): bool => $a->role === $role && $a->scope_type === $type)
            ->map(fn (RoleAssignment $a): int => $a->scope_id)
            ->all());

        return new AccessScope(
            organizationId: $this->organization->id,
            isChurchAdmin: $ids(RoleEnum::Admin, ScopeTypeEnum::Organization) !== [],
            isChurchLifeshaper: $ids(RoleEnum::Lifeshaper, ScopeTypeEnum::Organization) !== [],
            adminBranchIds: $ids(RoleEnum::Admin, ScopeTypeEnum::Branch),
            lifeshaperBranchIds: $ids(RoleEnum::Lifeshaper, ScopeTypeEnum::Branch),
            adminSeriesIds: $ids(RoleEnum::Admin, ScopeTypeEnum::EventSeries),
        );
    }
}
