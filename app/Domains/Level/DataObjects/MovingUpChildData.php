<?php

declare(strict_types=1);

namespace App\Domains\Level\DataObjects;

use App\Data\Models\MovingUpChild;
use App\Enums\ChildStatusEnum;
use App\Foundation\Support\LevelChart;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A child of the last Moving-Up, on its page: from and to ("Level 1" → "Level 2", or "Graduated"), and — once put
 * back — by whom and when ("Ana Admin · Sat 7 Nov 10:31", the church's time).
 */
#[TypeScript]
final class MovingUpChildData extends Data
{
    public function __construct(
        public int $child_id,
        public string $nickname,
        public string $full_name,
        public string $from,
        public string $to,
        public ?string $put_back,
    ) {}

    public static function fromModel(MovingUpChild $moved, string $timezone): self
    {
        $by = $moved->putBackBy->name ?? 'Someone';

        return new self(
            child_id: $moved->child_id,
            nickname: $moved->child->displayName(),
            full_name: $moved->child->fullName(),
            from: LevelChart::name($moved->from_level),
            to: $moved->to_status === ChildStatusEnum::Graduated ? 'Graduated' : LevelChart::name($moved->to_level),
            put_back: $moved->put_back_at === null ? null : $by.' · '.$moved->put_back_at->setTimezone($timezone)->format('D j M H:i'),
        );
    }
}
