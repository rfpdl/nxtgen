<?php

declare(strict_types=1);

namespace App\Services\Org\Data;

use App\Enums\DuplicateStatusEnum;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

/** A reviewer's answer on a possible duplicate: only "dismissed" or "confirmed" — never back to open. */
final class ResolveDuplicateInput extends Data
{
    public function __construct(public DuplicateStatusEnum $status) {}

    /** @return array<string, list<mixed>> */
    public static function rules(): array
    {
        return ['status' => ['required', Rule::in([DuplicateStatusEnum::Dismissed->value, DuplicateStatusEnum::Confirmed->value])]];
    }
}
