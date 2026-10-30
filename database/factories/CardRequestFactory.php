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
            // As the app leaves it: a request that reached Approved (and went on to be printed or given) was approved.
            'approved_at' => fn (array $attributes): mixed => in_array(self::step($attributes['step']), [CardStepEnum::Approved, CardStepEnum::Printed, CardStepEnum::Given], true) ? now() : null,
        ];
    }

    private static function step(mixed $value): ?CardStepEnum
    {
        return $value instanceof CardStepEnum ? $value : CardStepEnum::tryFrom(is_string($value) ? $value : '');
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
