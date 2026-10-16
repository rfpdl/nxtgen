<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\Models\Church;
use App\Data\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Group> */
final class GroupFactory extends Factory
{
    protected $model = Group::class;

    /** @return array<model-property<Group>, mixed> */
    public function definition(): array
    {
        return [
            'church_id' => Church::factory(),
            'name' => 'Level '.fake()->numberBetween(1, 4),
            'sort' => 0,
            'min_age' => 4,
            'max_age' => 12,
            'gender' => null,
            'is_active' => true,
        ];
    }
}
