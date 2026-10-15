<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Family;
use App\Foundation\Jobs\BaseJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Church-wide "is this family already registered?" (plan §7.2): parent name, email, phone digits, or a
 * child's name/nickname. Org-wide on purpose — same umbrella — and the caller only gets a lite projection.
 *
 * @extends BaseJob<Collection<int, Family>>
 */
final class SearchFamiliesJob extends BaseJob
{
    public function __construct(
        private readonly int $organizationId,
        private readonly string $query,
    ) {}

    /** @return Collection<int, Family> */
    public function handle(): Collection
    {
        $term = trim($this->query);
        if (mb_strlen($term) < 2) {
            return new Collection;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
        $digits = (string) preg_replace('/\D+/', '', $term);

        return Family::query()
            ->with(['primaryGuardian.user', 'homeBranch', 'children'])
            ->where('organization_id', $this->organizationId)
            ->where(fn (Builder $q) => $q
                ->whereHas('guardians', fn (Builder $g) => $g
                    ->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->when(strlen($digits) >= 4, fn (Builder $p) => $p->orWhere('phone', 'like', '%'.$digits.'%'))
                    ->orWhereHas('user', fn (Builder $u) => $u->where('email', 'like', $like)))
                ->orWhereHas('children', fn (Builder $c) => $c
                    ->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('nickname', 'like', $like)))
            ->limit(20)
            ->get();
    }
}
