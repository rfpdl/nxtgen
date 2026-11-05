<?php

declare(strict_types=1);

namespace App\Domains\Level\DataObjects;

use App\Data\Models\Child;
use App\Foundation\DataObjects\LevelData;
use App\Foundation\Support\LevelChart;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A child on the Moving-Up page, ticked to move (DECISIONS 2026-11-06): who they are, their satellite, their Level
 * now and where Moving-Up takes them ("Level 3", or "Graduated" from the top Level).
 */
#[TypeScript]
final class MovingUpCandidateData extends Data
{
    public function __construct(
        public int $id,
        public string $nickname,
        public string $full_name,
        public string $satellite,
        public LevelData $level,
        public string $moves_to,
    ) {}

    public static function fromChild(Child $child): self
    {
        $level = (int) $child->level;
        $next = LevelChart::movedUp($level);

        return new self(
            id: $child->id,
            nickname: $child->displayName(),
            full_name: $child->fullName(),
            satellite: $child->family->homeSatellite->name,
            level: LevelData::of($level),
            moves_to: $next === null ? 'Graduated' : LevelChart::name($next),
        );
    }
}
