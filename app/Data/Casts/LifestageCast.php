<?php

declare(strict_types=1);

namespace App\Data\Casts;

use App\Enums\LifestageEnum;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * A family's lifestage, read loosely (Astra 2026-11-07): a value stored before the three choices — "MARRIED",
 * "Married with kids" — reads as the choice it names, or as none given, never an error. Saved as the enum's value.
 *
 * @implements CastsAttributes<LifestageEnum|null, LifestageEnum|null>
 */
final class LifestageCast implements CastsAttributes
{
    /** @param  array<string, mixed>  $attributes */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?LifestageEnum
    {
        return LifestageEnum::fromLoose(is_string($value) ? $value : null);
    }

    /** @param  array<string, mixed>  $attributes */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value?->value;
    }
}
