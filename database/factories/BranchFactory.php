<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\Models\Branch;
use App\Data\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Branch> */
final class BranchFactory extends Factory
{
    protected $model = Branch::class;

    /** @return array<model-property<Branch>, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->city().' Satellite';

        return [
            'organization_id' => Organization::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
