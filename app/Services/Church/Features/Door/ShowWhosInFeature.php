<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Door;

use App\Data\Models\Session;
use App\Domains\CheckIn\DataObjects\DoorSessionData;
use App\Domains\CheckIn\DataObjects\SessionChoiceData;
use App\Domains\CheckIn\DataObjects\WhosInSquadData;
use App\Domains\CheckIn\Jobs\GetOpenSessionsJob;
use App\Domains\CheckIn\Jobs\GetSessionAttendanceJob;
use App\Domains\CheckIn\Jobs\GetSessionHeadCountsJob;
use App\Domains\Church\Jobs\GetChurchTodayJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Data\DoorQueryInput;
use App\Services\Church\Features\BaseChurchFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Who's in, the door's second tab (DECISIONS 2026-10-28 — roll call), at the Session's own address (spec item 16:
 * `/<code>/whos-in`): the children checked in to that Session (not undone), by Squad in the
 * Attendance order, each Squad with how many are marked present; each child's nickname, allergy flag and present
 * mark; each Squad's confirmed head count (who and when). `?squad=` picks one Squad (a Squad with no one in now falls
 * back to All). No photo, no phone.
 *
 * @extends BaseChurchFeature<Response>
 */
#[UserStory(
    title: 'See who is in',
    persona: 'Squad leader (any staff)',
    happyPaths: [
        'story#117' => 'Staff see the children checked in to a class by Squad, at its Session\'s own address — open, or closed and read-only — — Level, then mixed, boys, girls; children by nickname — each Squad with "N of M present", each child with the allergy flag and whether they are present, and can pick one Squad',
    ],
    unhappyPaths: ['story#118' => 'Only a Role at the Session\'s satellite covering its class — anyone else, or a code nobody has, is a 404; an undone child is not listed; never a photo, a phone number or a health detail'],
)]
#[AccessRow(AccessRowEnum::CheckIn)]
final class ShowWhosInFeature extends BaseChurchFeature
{
    public function __construct(private readonly Session $session) {}

    public function handle(DoorQueryInput $address): Response
    {
        $church = $this->church();
        $session = $this->session;
        $today = $this->run(new GetChurchTodayJob(church: $church));
        $squads = WhosInSquadData::groupsOf(
            $this->run(new GetSessionAttendanceJob(session: $session)),
            $this->run(new GetSessionHeadCountsJob(session: $session)),
            $church->timezone,
        );

        return Inertia::render('Church/Door/WhosIn', [
            // A closed Session's Who's in stays readable, and read-only (spec item 16; Roy 2026-11-10).
            'session' => DoorSessionData::fromModel($session, $today),
            // The bar's dropdown (Roy 2026-11-10): the satellite's classes open now whose door this person may open.
            'open_classes' => array_map(SessionChoiceData::fromModel(...), array_values(array_filter(
                $this->run(new GetOpenSessionsJob(satellite: $this->satellite(), today: $today)),
                fn (Session $open): bool => $this->scope()->canCheckInTo($open->schedule),
            ))),
            'squads' => $squads,
            'squad_id' => collect($squads)->contains(fn (WhosInSquadData $s): bool => $s->id === $address->squad) ? $address->squad : null,
        ]);
    }
}
