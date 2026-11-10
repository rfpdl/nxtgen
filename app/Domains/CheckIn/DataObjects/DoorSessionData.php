<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\DataObjects;

use App\Data\Models\Session;
use App\Enums\SessionStateEnum;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The Session a door or Who's in page is at (spec item 16): its code (the page's address), its class and times, its
 * date, and where it stands on the church's today (spec item 15) — the satellite is the bar's.
 */
#[TypeScript]
final class DoorSessionData extends Data
{
    public function __construct(
        public string $code,
        /** Its class (Schedule): an admin opens it again from here (serve.sessions.open). */
        public int $class_id,
        /** "1PM" */
        public string $name,
        /** "13:00–15:00" */
        public string $times,
        /** "Sat 31 Oct" */
        public string $date_label,
        public SessionStateEnum $state,
    ) {}

    public static function fromModel(Session $session, CarbonInterface $today): self
    {
        $class = $session->schedule;

        return new self(
            code: $session->code,
            class_id: $class->id,
            name: $class->name,
            times: "{$class->startsAt()}–{$class->endsAt()}",
            date_label: $session->date->format('D j M'),
            state: SessionStateEnum::of($session, $today),
        );
    }
}
