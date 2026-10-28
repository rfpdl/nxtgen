<?php

declare(strict_types=1);

namespace App\Domains\Family\DataObjects;

use App\Data\Models\Child;
use App\Foundation\DataObjects\LevelData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A child as a family's row on the staff lists and a search hit show it: the nickname and the Level (a pill in its
 * colour). Nothing else — no health detail, phone or photo.
 */
#[TypeScript]
final class ListChildData extends Data
{
    public function __construct(
        public string $nickname,
        public ?LevelData $level,
    ) {}

    public static function fromModel(Child $child): self
    {
        return new self(nickname: $child->nickname, level: LevelData::fromNumber($child->level));
    }
}
