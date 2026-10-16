<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\Models\Church;
use App\Data\Models\Satellite;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Satellite> */
final class SatelliteFactory extends Factory
{
    protected $model = Satellite::class;

    /** @return array<model-property<Satellite>, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->city().' Satellite';

        return [
            'church_id' => Church::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
