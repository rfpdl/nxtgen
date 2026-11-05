<?php

declare(strict_types=1);

namespace App\Domains\Level\DataObjects;

use App\Data\Models\MovingUp;
use App\Data\Models\MovingUpChild;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The church's last Moving-Up, on its page: when and by whom ("Sat 7 Nov 10:31", the church's time), whether it is
 * recent (the page then warns before another), and each child.
 */
#[TypeScript]
final class MovingUpData extends Data
{
    /** A Moving-Up this recent makes the page warn before another: it would move every child up a second Level. */
    public const int RECENT_DAYS = 30;

    /** @param list<MovingUpChildData> $children */
    public function __construct(
        public int $id,
        public string $made,
        public ?string $made_by,
        public bool $is_recent,
        public array $children,
    ) {}

    public static function fromModel(MovingUp $movingUp, string $timezone): self
    {
        return new self(
            id: $movingUp->id,
            made: $movingUp->created_at->setTimezone($timezone)->format('D j M Y H:i'),
            made_by: $movingUp->maker?->name,
            is_recent: $movingUp->created_at->greaterThan(now()->subDays(self::RECENT_DAYS)),
            children: array_values($movingUp->children->map(fn (MovingUpChild $m): MovingUpChildData => MovingUpChildData::fromModel($m, $timezone))->all()),
        );
    }
}
