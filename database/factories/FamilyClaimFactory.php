<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\Models\Family;
use App\Data\Models\FamilyClaim;
use App\Data\Models\Guardian;
use App\Enums\FamilyClaimStatusEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/** @extends Factory<FamilyClaim> */
final class FamilyClaimFactory extends Factory
{
    protected $model = FamilyClaim::class;

    /** @return array<model-property<FamilyClaim>, mixed> */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'church_id' => fn (array $a): mixed => Family::query()->whereKey(self::id($a['family_id']))->value('church_id'),
            'guardian_id' => fn (array $a): int => Guardian::factory()->create(['family_id' => self::id($a['family_id']), 'user_id' => null])->id,
            'email_normalised' => fake()->unique()->safeEmail(),
            'revision' => 1,
            'status' => FamilyClaimStatusEnum::Pending,
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
