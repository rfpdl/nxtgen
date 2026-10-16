<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\Models\Church;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Church> */
final class ChurchFactory extends Factory
{
    protected $model = Church::class;

    /** @return array<model-property<Church>, mixed> */
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
