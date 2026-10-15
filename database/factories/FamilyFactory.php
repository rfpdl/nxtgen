<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\Models\Branch;
use App\Data\Models\Family;
use App\Data\Models\Organization;
use App\Enums\RegistrationSourceEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/** @extends Factory<Family> */
final class FamilyFactory extends Factory
{
    protected $model = Family::class;

    /** @return array<model-property<Family>, mixed> */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'home_branch_id' => fn (array $attributes): int => Branch::factory()->create(['organization_id' => self::id($attributes['organization_id'])])->id,
            'area_text' => fake()->randomElement(['Al Barsha', 'Deira', 'Karama', 'JVC', 'Al Nahda']),
            'registration_source' => RegistrationSourceEnum::Self,
            'privacy_consented_at' => now(),
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
