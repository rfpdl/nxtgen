<?php

declare(strict_types=1);

namespace App\Domains\Registry\DataObjects;

use App\Foundation\Attributes\NonClientFacing;

#[NonClientFacing]
final readonly class ImportResultData
{
    /**
     * @param  list<string>  $problems
     * @param  list<string>  $awaitingClaim  emails that are already someone's login: the family waits for that
     *                                       person to confirm (no email is sent by the import — review 3, B3)
     */
    public function __construct(
        public int $familiesCreated,
        public int $childrenCreated,
        public int $childrenSkipped,
        public array $problems,
        public array $awaitingClaim = [],
    ) {}
}
