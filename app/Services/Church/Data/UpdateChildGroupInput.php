<?php

declare(strict_types=1);

namespace App\Services\Church\Data;

use Spatie\LaravelData\Data;

/** A church admin's group choice for one child; the Feature checks it is this church's and fits the child. */
final class UpdateChildGroupInput extends Data
{
    public function __construct(public int $group_id) {}
}
