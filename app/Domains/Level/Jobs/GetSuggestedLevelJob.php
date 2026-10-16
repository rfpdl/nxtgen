<?php

declare(strict_types=1);

namespace App\Domains\Level\Jobs;

use App\Data\Models\Church;
use App\Foundation\Jobs\BaseJob;
use App\Foundation\Support\LevelChart;
use Carbon\CarbonImmutable;

/**
 * The Level a child's age points to, on the church's Moving-Up reference date — a SUGGESTION when a child is
 * registered, added or imported. The child then stores the Level and keeps it until Moving-Up; it never changes
 * by itself (GLOSSARY "Level").
 *
 * Age is completed whole years: Carbon's diffInYears returns a float, and 5.997 matched no band in the PoC. Null
 * when no Level fits the age (too young or too old) — the child has no Level yet, for staff to see.
 *
 * @extends BaseJob<int|null>
 */
final class GetSuggestedLevelJob extends BaseJob
{
    public function __construct(
        private readonly Church $church,
        private readonly string $dateOfBirth,
    ) {}

    public function handle(): ?int
    {
        $reference = CarbonImmutable::parse($this->church->moving_up_reference_date ?? now());
        $age = (int) floor(CarbonImmutable::parse($this->dateOfBirth)->diffInYears($reference, absolute: true));

        return LevelChart::forAge($age);
    }
}
