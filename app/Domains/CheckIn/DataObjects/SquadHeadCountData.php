<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\DataObjects;

use App\Data\Models\SquadHeadCount;
use Illuminate\Support\Str;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A Squad's confirmed head count (roll call, DECISIONS 2026-10-28): "✓ Confirmed 13:20 by Joy (4)". Needs the
 * count's countedBy loaded.
 */
#[TypeScript]
final class SquadHeadCountData extends Data
{
    public function __construct(
        public int $count,
        /** "13:20", in the church's timezone */
        public string $counted_at,
        /** The Staff member's first name; "" if their login has gone. */
        public string $counted_by,
    ) {}

    public static function fromModel(SquadHeadCount $headCount, string $timezone): self
    {
        return new self(
            count: $headCount->head_count,
            counted_at: $headCount->counted_at->setTimezone($timezone)->format('H:i'),
            counted_by: Str::before(trim($headCount->countedBy->name ?? ''), ' '),
        );
    }
}
