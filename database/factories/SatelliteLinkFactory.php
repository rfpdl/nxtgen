<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\Models\Satellite;
use App\Data\Models\SatelliteLink;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/** @extends Factory<SatelliteLink> */
final class SatelliteLinkFactory extends Factory
{
    protected $model = SatelliteLink::class;

    /** @return array<model-property<SatelliteLink>, mixed> */
    public function definition(): array
    {
        return [
            'satellite_id' => Satellite::factory(),
            'church_id' => fn (array $attributes): mixed => Satellite::query()->whereKey(self::id($attributes['satellite_id']))->value('church_id'),
            'label' => 'Parents\' WhatsApp group',
            'url' => 'https://chat.whatsapp.com/'.fake()->unique()->bothify('????????'),
            'position' => fake()->unique()->numberBetween(1, 30000),
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
