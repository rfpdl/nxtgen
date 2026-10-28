<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\Models\Session;
use App\Data\Models\Squad;
use App\Data\Models\SquadHeadCount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/** @extends Factory<SquadHeadCount> */
final class SquadHeadCountFactory extends Factory
{
    protected $model = SquadHeadCount::class;

    /** @return array<model-property<SquadHeadCount>, mixed> */
    public function definition(): array
    {
        return [
            'session_id' => Session::factory(),
            'church_id' => fn (array $attributes): mixed => Session::query()->whereKey(self::id($attributes['session_id']))->value('church_id'),
            // The Session's own satellite's Squad, so the count never crosses a church.
            'squad_id' => fn (array $attributes): mixed => Squad::factory()->create([
                'satellite_id' => Session::query()->whereKey(self::id($attributes['session_id']))->value('satellite_id'),
            ])->id,
            'head_count' => 4,
            'counted_at' => now(),
            'counted_by' => null,
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
