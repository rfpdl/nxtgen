<?php

declare(strict_types=1);

namespace App\Services\Church\Data;

use App\Enums\ChildStatusEnum;
use Spatie\LaravelData\Data;

final class UpdateChildStatusInput extends Data
{
    public function __construct(public ChildStatusEnum $status) {}
}
