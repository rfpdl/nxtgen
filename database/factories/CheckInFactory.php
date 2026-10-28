<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\Models\CheckIn;
use App\Data\Models\Child;
use App\Data\Models\Session;
use App\Data\Models\Squad;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/** @extends Factory<CheckIn> */
final class CheckInFactory extends Factory
{
    protected $model = CheckIn::class;

    /** @return array<model-property<CheckIn>, mixed> */
    public function definition(): array
    {
        return [
            'session_id' => Session::factory(),
            'church_id' => fn (array $attributes): mixed => Session::query()->whereKey(self::id($attributes['session_id']))->value('church_id'),
            'child_id' => Child::factory(),
            'squad_id' => Squad::factory(),
            'revision' => 0,
            'checked_in_at' => now(),
            'checked_in_by' => null,
            'present_at' => null,
            'present_by' => null,
            'undone_at' => null,
            'undone_by' => null,
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
