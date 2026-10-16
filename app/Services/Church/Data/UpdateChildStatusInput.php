<?php

declare(strict_types=1);

namespace App\Services\Church\Data;

use App\Enums\ChildStatusEnum;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class UpdateChildStatusInput extends Data
{
    public function __construct(public ChildStatusEnum $status) {}
}
