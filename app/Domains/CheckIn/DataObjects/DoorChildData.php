<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\DataObjects;

use App\Data\Models\Child;
use App\Enums\ChildStatusEnum;
use App\Foundation\DataObjects\LevelData;
use App\Foundation\Support\LevelChart;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A child on a family card at the door — church-wide, so the lite view (plan §3.3): no photo, no phone, and of the
 * health details only an allergy yes/no (`has_allergies`, the ⚠); the details come only when the row is opened
 * (ChildHealthData). `squad` is THIS satellite's Squad for the child's Level and gender — the name follows from the
 * chart, so it is the same at every satellite. `check_in_id` is set while the child is in the chosen class today.
 */
#[TypeScript]
final class DoorChildData extends Data
{
    public function __construct(
        public int $id,
        public string $nickname,
        public string $full_name,
        public ?LevelData $level,
        public ?string $squad,
        public ChildStatusEnum $status,
        public string $status_label,
        public bool $has_allergies,
        public ?int $check_in_id,
        /** The Level the child's age points to — pre-selected when the door picks a Level for a child with none. */
        public ?int $suggested_level,
    ) {}

    public static function fromModel(Child $child, ?int $checkInId, ?int $suggestedLevel): self
    {
        $level = $child->level;

        return new self(
            id: $child->id,
            nickname: $child->displayName(),
            full_name: $child->fullName(),
            level: LevelData::fromNumber($level),
            squad: $level === null ? null : LevelChart::squadName($level, LevelChart::squadGender($level, $child->gender)),
            status: $child->status,
            status_label: $child->status->label(),
            has_allergies: $child->hasAllergies(),
            check_in_id: $checkInId,
            suggested_level: $level === null ? $suggestedLevel : null,
        );
    }
}
