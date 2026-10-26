<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\Models\Satellite;
use App\Data\Models\Schedule;
use App\Enums\WeekdayEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/** @extends Factory<Schedule> */
final class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    /** @return array<model-property<Schedule>, mixed> */
    public function definition(): array
    {
        return [
            'satellite_id' => Satellite::factory(),
            'church_id' => fn (array $attributes): mixed => Satellite::query()->whereKey(self::id($attributes['satellite_id']))->value('church_id'),
            'name' => fake()->unique()->numerify('Class ###'),
            'weekday' => WeekdayEnum::Saturday,
            'starts_at' => '13:00',
            'ends_at' => '15:00',
            'is_active' => true,
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
