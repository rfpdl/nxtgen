<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\Models\Child;
use App\Data\Models\Family;
use App\Enums\ChildStatusEnum;
use App\Enums\GenderEnum;
use App\Enums\RegistrationSourceEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/** @extends Factory<Child> */
final class ChildFactory extends Factory
{
    protected $model = Child::class;

    /** @return array<model-property<Child>, mixed> */
    public function definition(): array
    {
        $gender = fake()->randomElement(GenderEnum::cases());
        $first = fake()->firstName($gender === GenderEnum::Boy ? 'male' : 'female');

        return [
            'family_id' => Family::factory(),
            'church_id' => fn (array $attributes): mixed => Family::query()->whereKey(self::id($attributes['family_id']))->value('church_id'),
            'first_name' => $first,
            'last_name' => fake()->lastName(),
            'nickname' => $first,
            'gender' => $gender,
            'date_of_birth' => fake()->dateTimeBetween('-12 years', '-4 years')->format('Y-m-d'),
            'status' => ChildStatusEnum::default(),
            'registration_source' => RegistrationSourceEnum::Self,
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
