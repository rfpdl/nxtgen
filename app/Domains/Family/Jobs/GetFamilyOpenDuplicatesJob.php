<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Data\Models\DuplicateCandidate;
use App\Data\Models\Family;
use App\Enums\DuplicateStatusEnum;
use App\Foundation\Jobs\BaseJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A family's unanswered possible duplicates, newest first: the pairs it is on — as the new family or the existing
 * one, itself or through one of its children — with both sides' families loaded for the family page. The caller
 * decides which ones the viewer may answer.
 *
 * @extends BaseJob<Collection<int, DuplicateCandidate>>
 */
final class GetFamilyOpenDuplicatesJob extends BaseJob
{
    public function __construct(private readonly Family $family) {}

    /** @return Collection<int, DuplicateCandidate> */
    public function handle(): Collection
    {
        $loaded = ['primaryGuardian.user', 'homeSatellite', 'children'];
        $sides = fn (MorphTo $m) => $m->morphWith([
            Family::class => $loaded,
            Child::class => array_map(fn (string $r): string => "family.{$r}", $loaded),
        ]);
        $childIds = $this->family->children()->pluck('id')->all();

        return DuplicateCandidate::query()
            ->with(['subject' => $sides, 'candidate' => $sides])
            ->where('church_id', $this->family->church_id)
            ->where('status', DuplicateStatusEnum::Open)
            ->where(fn (Builder $q) => $q
                ->where(fn (Builder $f) => self::onEitherSide($f, 'family', [$this->family->id]))
                ->orWhere(fn (Builder $c) => self::onEitherSide($c, 'child', $childIds)))
            ->latest('id')
            ->get();
    }

    /**
     * Pairs of this kind with one of these records on either side.
     *
     * @param  Builder<DuplicateCandidate>  $query
     * @param  array<array-key, mixed>  $ids
     */
    private static function onEitherSide(Builder $query, string $type, array $ids): void
    {
        $query->where('subject_type', $type)
            ->where(fn (Builder $side) => $side->whereIn('subject_id', $ids)->orWhereIn('candidate_id', $ids));
    }
}
