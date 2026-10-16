<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Data\Models\DuplicateCandidate;
use App\Data\Models\Family;
use App\Enums\DuplicateStatusEnum;
use App\Foundation\Jobs\BaseJob;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Open possible duplicates in this church, newest first, with both sides' families loaded for the review
 * screen. The caller decides which ones the viewer may review.
 *
 * @extends BaseJob<Collection<int, DuplicateCandidate>>
 */
final class GetOpenDuplicatesJob extends BaseJob
{
    public function __construct(private readonly int $churchId) {}

    /** @return Collection<int, DuplicateCandidate> */
    public function handle(): Collection
    {
        $family = ['primaryGuardian.user', 'homeSatellite', 'children'];
        $sides = fn (MorphTo $m) => $m->morphWith([
            Family::class => $family,
            Child::class => array_map(fn (string $r): string => "family.{$r}", $family),
        ]);

        return DuplicateCandidate::query()
            ->with(['subject' => $sides, 'candidate' => $sides])
            ->where('church_id', $this->churchId)
            ->where('status', DuplicateStatusEnum::Open)
            ->latest('id')
            ->get();
    }
}
