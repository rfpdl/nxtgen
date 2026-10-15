<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\Models\Family;
use App\Data\Models\Guardian;
use App\Enums\RelationshipEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/** @extends Factory<Guardian> */
final class GuardianFactory extends Factory
{
    protected $model = Guardian::class;

    /** @return array<model-property<Guardian>, mixed> */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'organization_id' => fn (array $attributes): mixed => Family::query()->whereKey(self::id($attributes['family_id']))->value('organization_id'),
            'user_id' => null,
            'relationship' => RelationshipEnum::Mother,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => '05'.fake()->numerify('########'),
            'can_pick_up' => true,
        ];
    }

    /** Factory attributes arrive as mixed; a related id is always an int (or a model). */
    private static function id(mixed $value): int
    {
        if ($value instanceof Model) {
            $value = $value->getKey();
        }

        return is_numeric($value) ? (int) $value : throw new \InvalidArgumentException('Expected an id.');
    }
}
