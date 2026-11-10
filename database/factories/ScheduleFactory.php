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
            // Two hours after the start (never past 23:59), so a test that names only a start still ends after it.
            'ends_at' => fn (array $attributes): string => self::time($attributes['starts_at']) >= '22:00' ? '23:59' : date('H:i', (int) strtotime(self::time($attributes['starts_at']).' +2 hours')),
            'is_active' => true,
        ];
    }

    private static function time(mixed $value): string
    {
        return is_string($value) ? $value : throw new \InvalidArgumentException('Expected a time.');
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
