<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\Models\CardRequest;
use App\Data\Models\Child;
use App\Enums\CardReasonEnum;
use App\Enums\CardStepEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/** @extends Factory<CardRequest> */
final class CardRequestFactory extends Factory
{
    protected $model = CardRequest::class;

    /** @return array<model-property<CardRequest>, mixed> */
    public function definition(): array
    {
        return [
            'child_id' => Child::factory(),
            // The child's own church, so the request never crosses a church.
            'church_id' => fn (array $attributes): mixed => Child::query()->whereKey(self::id($attributes['child_id']))->value('church_id'),
            'reason' => CardReasonEnum::Lost,
            'note' => null,
            'step' => CardStepEnum::Asked,
            'fee_minor' => CardReasonEnum::REPLACEMENT_FEE_MINOR,
            'created_by' => null,
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
