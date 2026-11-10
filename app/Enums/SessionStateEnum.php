<?php

declare(strict_types=1);

namespace App\Enums;

use App\Data\Models\Session;
use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Carbon\CarbonInterface;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Where today's Session of a class stands (spec item 15): not opened yet (no Session), open, or closed — by an admin,
 * or at midnight. Only while open does anything at its door change (Roy 2026-11-10).
 */
#[TypeScript]
enum SessionStateEnum: string implements HasEnumMetadata
{
    use ProvidesEnumMetadata;

    case NotOpen = 'not_open';
    case Open = 'open';
    case Closed = 'closed';

    /** `$session` — the class's Session for `$today`, if it was opened — on the church's today. */
    public static function of(?Session $session, CarbonInterface $today): self
    {
        return match (true) {
            $session === null => self::NotOpen,
            $session->isOpenOn($today) => self::Open,
            default => self::Closed,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::NotOpen => 'Not open yet',
            self::Open => 'Open',
            self::Closed => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NotOpen => 'neutral',
            self::Open => 'success',
            self::Closed => 'warning',
        };
    }

    /** Why nothing at `$class`'s door can change now — null while it is open. */
    public function refusal(string $class): ?string
    {
        return match ($this) {
            self::NotOpen => "{$class} is not open yet.",
            self::Open => null,
            self::Closed => "{$class} is closed.",
        };
    }
}
