<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Organization> */
final class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    /** @return array<model-property<Organization>, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->company().' Church';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'timezone' => 'Asia/Dubai',
            'currency' => 'AED',
            'moving_up_reference_date' => '2027-02-01',
            'settings' => ['satellite_label' => 'Satellite', 'lifeshapers_can_open' => true],
        ];
    }
}
