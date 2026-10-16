<?php

declare(strict_types=1);

namespace App\Domains\Level\DataObjects;

use App\Foundation\Support\LevelChart;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One Level as a page offers it: the number a form sends, the name a person reads ("Level 2"). */
#[TypeScript]
final class LevelOptionData extends Data
{
    public function __construct(
        public int $level,
        public string $name,
    ) {}

    public static function fromLevel(int $level): self
    {
        return new self(level: $level, name: LevelChart::name($level));
    }
}
