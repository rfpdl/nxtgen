<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\Models\Schedule;
use App\Data\Models\Session;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/** @extends Factory<Session> */
final class SessionFactory extends Factory
{
    protected $model = Session::class;

    /** @return array<model-property<Session>, mixed> */
    public function definition(): array
    {
        return [
            'schedule_id' => Schedule::factory(),
            'satellite_id' => fn (array $attributes): mixed => Schedule::query()->whereKey(self::id($attributes['schedule_id']))->value('satellite_id'),
            'church_id' => fn (array $attributes): mixed => Schedule::query()->whereKey(self::id($attributes['schedule_id']))->value('church_id'),
            'date' => '2026-10-31',
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
