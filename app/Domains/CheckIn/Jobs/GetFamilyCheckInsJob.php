<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Data\Models\CheckIn;
use App\Data\Models\Family;
use App\Foundation\Jobs\BaseJob;

/**
 * A family's children's attendance, for the staff family page (slice 3, DECISIONS 2026-10-28 night): every Check-in
 * of its children that was not undone — newest Session first (by date, then the later class) — with its class,
 * satellite and Squad, and whether the child was marked present. The same few queries however many there are.
 *
 * @extends BaseJob<list<CheckIn>>
 */
final class GetFamilyCheckInsJob extends BaseJob
{
    public function __construct(private readonly Family $family) {}

    /** @return list<CheckIn> */
    public function handle(): array
    {
        return array_values(CheckIn::query()
            ->where('church_id', $this->family->church_id)
            ->whereIn('child_id', $this->family->childIds())
            ->whereNull('undone_at')
            ->with(['session.schedule', 'session.satellite', 'squad'])
            ->get()
            ->sortBy([
                fn (CheckIn $a, CheckIn $b): int => $b->session->date <=> $a->session->date,
                fn (CheckIn $a, CheckIn $b): int => ($b->session->schedule->startsAt() ?? '') <=> ($a->session->schedule->startsAt() ?? ''),
                fn (CheckIn $a, CheckIn $b): int => $b->id <=> $a->id,
            ])
            ->all());
    }
}
