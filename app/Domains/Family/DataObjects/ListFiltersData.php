<?php

declare(strict_types=1);

namespace App\Domains\Family\DataObjects;

use App\Enums\ChildStatusEnum;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A registration list's filters as the page shows them — named as the address parameters the filter bar sends back
 * (`?q=&satellite=&status=&level=&unconfirmed=1&duplicates=1`, FamilyListQuery / ChildListQuery). One shape for
 * both lists, so one filter bar serves both; the children list never has the two family-only toggles on.
 */
#[TypeScript]
final class ListFiltersData extends Data
{
    public function __construct(
        public string $q,
        public ?int $satellite,
        public ?ChildStatusEnum $status,
        public ?int $level,
        public bool $unconfirmed,
        public bool $duplicates,
    ) {}

    public static function fromFamilyFilters(FamilyListFiltersData $f): self
    {
        return new self(
            q: $f->query ?? '',
            satellite: $f->satelliteId,
            status: $f->status,
            level: $f->level,
            unconfirmed: $f->unconfirmedOnly,
            duplicates: $f->duplicatesOnly,
        );
    }

    public static function fromChildFilters(ChildListFiltersData $f): self
    {
        return new self(q: $f->query ?? '', satellite: $f->satelliteId, status: $f->status, level: $f->level, unconfirmed: false, duplicates: false);
    }
}
