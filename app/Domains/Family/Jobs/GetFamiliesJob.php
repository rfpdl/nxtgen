<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Family;
use App\Domains\Family\DataObjects\FamilyListFiltersData;
use App\Enums\DuplicateStatusEnum;
use App\Foundation\Jobs\BaseJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * The registration list (and its Excel download): families in the viewer's satellites, narrowed by the
 * filters. `$satelliteIds` null = every satellite; a chosen satellite outside them yields nothing, never more.
 *
 * @extends BaseJob<Collection<int, Family>>
 */
final class GetFamiliesJob extends BaseJob
{
    /** @param list<int>|null $satelliteIds */
    public function __construct(
        private readonly int $churchId,
        private readonly ?array $satelliteIds,
        private readonly FamilyListFiltersData $filters = new FamilyListFiltersData,
    ) {}

    /** @return Collection<int, Family> */
    public function handle(): Collection
    {
        $f = $this->filters;

        return Family::query()
            ->with(['primaryGuardian.user', 'homeSatellite', 'children.group'])
            ->where('church_id', $this->churchId)
            ->when($this->satelliteIds !== null, fn (Builder $q) => $q->whereIn('home_satellite_id', $this->satelliteIds ?? []))
            ->when($f->satelliteId !== null, fn (Builder $q) => $q->where('home_satellite_id', $f->satelliteId))
            ->when($f->query !== null, fn (Builder $q) => $q->matching((string) $f->query))
            ->when($f->status !== null, fn (Builder $q) => $q->whereHas('children', fn (Builder $c) => $c->where('status', $f->status)))
            ->when($f->groupId !== null, fn (Builder $q) => $q->whereHas('children', fn (Builder $c) => $c->where('group_id', $f->groupId)))
            ->when($f->unconfirmedOnly, fn (Builder $q) => $q->whereHas('primaryGuardian', fn (Builder $g) => $g
                ->whereNull('user_id')
                ->orWhereHas('user', fn (Builder $u) => $u->whereNull('email_verified_at'))))
            ->when($f->duplicatesOnly, fn (Builder $q) => $q->whereExists(fn (QueryBuilder $d) => $d
                ->from('duplicate_candidates')
                ->where('duplicate_candidates.status', DuplicateStatusEnum::Open->value)
                ->where(fn (QueryBuilder $side) => $side
                    ->where(fn (QueryBuilder $fam) => $fam->where('subject_type', 'family')
                        ->where(fn (QueryBuilder $id) => $id->whereColumn('subject_id', 'families.id')->orWhereColumn('candidate_id', 'families.id')))
                    ->orWhere(fn (QueryBuilder $kid) => $kid->where('subject_type', 'child')
                        ->whereExists(fn (QueryBuilder $c) => $c->from('children')
                            ->whereColumn('children.family_id', 'families.id')
                            ->where(fn (QueryBuilder $id) => $id
                                ->whereColumn('children.id', 'duplicate_candidates.subject_id')
                                ->orWhereColumn('children.id', 'duplicate_candidates.candidate_id')))))))
            ->latest('id')
            ->get();
    }
}
