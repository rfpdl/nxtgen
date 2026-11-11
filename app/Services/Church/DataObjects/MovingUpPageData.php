<?php

declare(strict_types=1);

namespace App\Services\Church\DataObjects;

use App\Domains\Level\DataObjects\MovingUpCandidateData;
use App\Domains\Level\DataObjects\MovingUpData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Moving-Up: who moves up, how many have no Level yet, and the last Moving-Up (Church/MovingUp/Index) — its props, ONE contract with the page (TODO #43). */
#[TypeScript]
final class MovingUpPageData extends Data
{
    public function __construct(
        /** @var list<MovingUpCandidateData> */
        public array $children,
        /** active children with no Level yet: not moved */
        public int $without_level,
        public ?MovingUpData $last,
    ) {}
}
