<?php

declare(strict_types=1);

namespace App\Domains\Group\Jobs;

use App\Data\Models\Church;
use App\Data\Models\Group;
use App\Enums\GenderEnum;
use App\Foundation\Jobs\BaseJob;
use Carbon\CarbonImmutable;

/**
 * The group a child's age (on the church's Moving-Up reference date) and gender point to — a SUGGESTION
 * for registration only; groups otherwise change only at Moving-Up (plan §5).
 *
 * Age is completed whole years: Carbon's diffInYears returns a float, and 5.997 matched no band in the PoC.
 * Exactly one matching group, or null ("unassigned", flagged for staff). A gender-specific group wins over a
 * mixed one for the same age.
 *
 * @extends BaseJob<Group|null>
 */
final class GetSuggestedGroupJob extends BaseJob
{
    public function __construct(
        private readonly Church $church,
        private readonly GenderEnum $gender,
        private readonly string $dateOfBirth,
    ) {}

    public function handle(): ?Group
    {
        $reference = CarbonImmutable::parse($this->church->moving_up_reference_date ?? now());
        $age = (int) floor(CarbonImmutable::parse($this->dateOfBirth)->diffInYears($reference, absolute: true));

        $matches = Group::query()
            ->where('church_id', $this->church->id)
            ->where('is_active', true)
            ->where('min_age', '<=', $age)
            ->where('max_age', '>=', $age)
            ->where(fn ($q) => $q->where('gender', $this->gender)->orWhereNull('gender'))
            ->get();

        $specific = $matches->filter(fn (Group $g): bool => $g->gender !== null);
        $pool = $specific->isNotEmpty() ? $specific : $matches;

        return $pool->count() === 1 ? $pool->first() : null;
    }
}
