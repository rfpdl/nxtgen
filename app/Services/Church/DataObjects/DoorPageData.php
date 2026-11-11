<?php

declare(strict_types=1);

namespace App\Services\Church\DataObjects;

use App\Domains\CheckIn\DataObjects\ChildHealthData;
use App\Domains\CheckIn\DataObjects\DoorCheckInData;
use App\Domains\CheckIn\DataObjects\DoorFamilyData;
use App\Domains\CheckIn\DataObjects\DoorScanData;
use App\Domains\CheckIn\DataObjects\DoorSessionData;
use App\Domains\CheckIn\DataObjects\SessionChoiceData;
use App\Domains\Level\DataObjects\LevelOptionData;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A Session's door (Church/Door/Index) — its props, ONE contract with the page (TODO #43). */
#[TypeScript]
final class DoorPageData extends Data
{
    public function __construct(
        /** The Session at this address (spec item 16): its class, date and state (spec item 15). */
        public DoorSessionData $session,
        /** @var list<SessionChoiceData> the bar's dropdown: the satellite's classes open now that this person may open */
        public array $open_classes,
        /** An admin of the class: closes it here, and opens it again. */
        public bool $can_open,
        /** @var list<DoorCheckInData> */
        public array $checked_in,
        public string $query,
        /** The search was a name card (slice 3): its code and the children it named. */
        public ?DoorScanData $scan,
        /** @var list<DoorFamilyData> */
        public array $families,
        /** @var list<LevelOptionData> */
        public array $levels,
        /** One child's health details, asked for when their row is opened (`?health=` on a partial reload) — never sent otherwise. */
        #[Optional]
        public Lazy|ChildHealthData|null $health,
    ) {}
}
