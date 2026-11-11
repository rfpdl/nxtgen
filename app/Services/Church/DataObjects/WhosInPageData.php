<?php

declare(strict_types=1);

namespace App\Services\Church\DataObjects;

use App\Domains\CheckIn\DataObjects\DoorSessionData;
use App\Domains\CheckIn\DataObjects\SessionChoiceData;
use App\Domains\CheckIn\DataObjects\WhosInSquadData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Who's in at a Session (Church/Door/WhosIn): the Session, the bar's open classes, its Squads and the one picked — its props, ONE contract with the page (TODO #43). */
#[TypeScript]
final class WhosInPageData extends Data
{
    public function __construct(
        public DoorSessionData $session,
        /** @var list<SessionChoiceData> the bar's dropdown: the satellite's classes open now that this person may open */
        public array $open_classes,
        /** @var list<WhosInSquadData> */
        public array $squads,
        public ?int $squad_id,
    ) {}
}
