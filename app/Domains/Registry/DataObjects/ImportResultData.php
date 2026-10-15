<?php

declare(strict_types=1);

namespace App\Domains\Registry\DataObjects;

final readonly class ImportResultData
{
    /** @param list<string> $problems */
    public function __construct(
        public int $familiesCreated,
        public int $childrenCreated,
        public int $childrenSkipped,
        public array $problems,
    ) {}
}
