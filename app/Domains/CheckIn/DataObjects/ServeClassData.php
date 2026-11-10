<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\DataObjects;

use App\Data\Models\Schedule;
use App\Data\Models\Session;
use App\Enums\SessionStateEnum;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One of today's classes on Serve's first page (spec items 16–17): where it stands (spec item 15), its door's code
 * once it has been opened, and whether this person may open it.
 */
#[TypeScript]
final class ServeClassData extends Data
{
    public function __construct(
        public int $id,
        /** "1PM" */
        public string $name,
        /** "13:00–15:00" */
        public string $times,
        public SessionStateEnum $state,
        /** Its door is `/<code>/checkin`; null until an admin opens it. */
        public ?string $code,
        public bool $can_open,
    ) {}

    public static function fromModel(Schedule $class, ?Session $session, CarbonInterface $today, bool $canOpen): self
    {
        return new self(
            id: $class->id,
            name: $class->name,
            times: "{$class->startsAt()}–{$class->endsAt()}",
            state: SessionStateEnum::of($session, $today),
            code: $session?->code,
            can_open: $canOpen,
        );
    }
}
