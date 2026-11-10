<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Door;

use App\Data\Models\CheckIn;
use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Data\Models\Family;
use App\Data\Models\Session;
use App\Domains\CheckIn\DataObjects\ChildHealthData;
use App\Domains\CheckIn\DataObjects\DoorCheckInData;
use App\Domains\CheckIn\DataObjects\DoorFamilyData;
use App\Domains\CheckIn\DataObjects\DoorScanData;
use App\Domains\CheckIn\DataObjects\DoorSessionData;
use App\Domains\CheckIn\Jobs\GetCheckedInChildrenJob;
use App\Domains\CheckIn\Jobs\GetDoorCardJob;
use App\Domains\Church\Jobs\GetChurchTodayJob;
use App\Domains\Family\Jobs\GetChurchChildJob;
use App\Domains\Family\Jobs\SearchFamiliesJob;
use App\Domains\Level\Jobs\GetLevelsJob;
use App\Domains\Level\Jobs\GetSuggestedLevelJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Data\DoorQueryInput;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The door (DECISIONS 2026-10-26), phone first, at its Session's own address (spec item 16: `/<code>/checkin`, which
 * RequireSessionAssignment opens only to staff whose Role covers the class): the Session — its class, date and where
 * it stands (spec item 15), with Close for its admin; who is checked in to it, newest
 * first (DECISIONS 2026-10-27 — the page is never empty before a search; its length is the count); and the
 * church-wide family search (SearchFamiliesJob, the same one as registering — Q8) with each child's Level, Squad, an
 * allergy flag and whether they are in, and whether the viewer may open each family (`can_open`). No photo, no phone; a child's health details only when their row is opened
 * (`?health=`, a partial reload of `health`).
 *
 * @extends BaseChurchFeature<Response>
 */
#[UserStory(
    title: 'Open the door',
    persona: 'Lifeshaper (any staff)',
    happyPaths: [
        'story#103' => 'Staff at an open class\'s door see how many are checked in, and find any family of the church with each child\'s Level, Squad, allergy flag and whether they are in',
        'story#105' => 'Opening a child\'s row shows their health details, status and Level, and the family page link when the viewer may open the family',
        'story#116' => 'Before and below any search, staff see who is checked in to the chosen class today, newest first — nickname, Squad and time — and can undo one from there',
        'story#206' => 'Opening a class gives it a code: its door is `/<code>/checkin` and its Who\'s in `/<code>/whos-in`, for staff whose Role at its satellite covers the class; opened again the same day it keeps the code; closed, its Who\'s in stays readable',
        'story#128' => 'A name card\'s code — read by the camera or typed by a desk scanner — finds the child\'s family with the child ticked when ONE child matches exactly; several, or a close match, show the families with nobody ticked and a note',
    ],
    unhappyPaths: [
        'story#104' => 'Only a Role at the Session\'s satellite covering its class opens its door — anyone else, a code nobody has or another church\'s is a 404; the search never carries a photo, a phone number or a health detail',
        'story#207' => 'Staff of another satellite, another class\'s class admin, someone with no Role, or a code nobody has: not found; a stranger signs in first; a closed Session takes no check-in',
        'story#129' => 'A card no child matches says so (a typed word is an ordinary search, saying so only when nothing is found); only this church\'s children; never two different middle names, never fuzzier',
    ],
)]
#[AccessRow(AccessRowEnum::CheckIn)]
final class ShowDoorFeature extends BaseChurchFeature
{
    public function __construct(private readonly Session $session) {}

    public function handle(DoorQueryInput $address): Response
    {
        $church = $this->church();
        $session = $this->session;
        $today = $this->run(new GetChurchTodayJob(church: $church));
        $checkIns = $this->run(new GetCheckedInChildrenJob(session: $session));
        $checkedIn = [];   // child id => check-in id: each found child's ✓ and Undo
        foreach ($checkIns as $checkIn) {
            $checkedIn[$checkIn->child_id] = $checkIn->id;
        }

        $query = trim(request()->string('q')->value());
        [$families, $scan] = $this->search($church, $query, $address->card);
        $suggested = $this->suggestedLevels($families);

        return Inertia::render('Church/Door/Index', [
            // Spec item 15: check-in only while open; an admin of the class closes it here, and opens it again.
            'session' => DoorSessionData::fromModel($session, $today),
            // Opening again is only on its own day (Astra, issue 12): another day's Open would open TODAY's Session.
            'can_open' => $session->date->isSameDay($today) && $this->scope()->canOpenSessionOf($session->schedule),
            'checked_in' => array_map(fn (CheckIn $c): DoorCheckInData => DoorCheckInData::fromCheckIn($c, $church->timezone), $checkIns),
            'query' => $query,
            'scan' => $scan,
            'families' => array_values($families->map(fn (Family $f): DoorFamilyData => DoorFamilyData::fromModel($f, $checkedIn, $suggested, canOpen: $this->scope()->canSeeFamily($f)))->all()),
            'levels' => $this->run(new GetLevelsJob),
            'health' => Inertia::optional(fn (): ?ChildHealthData => ! request()->filled('health')
                ? null
                : ChildHealthData::fromModel($this->run(new GetChurchChildJob(church: $church, id: request()->integer('health'))))),
        ]);
    }

    /**
     * The families to show, and the name card the search was (slice 3, GetDoorCardJob): a card's families, or the
     * church-wide family search — for a typed word that matched no child's card too, which then says "No child matches
     * this card" only when no family is found either.
     *
     * @return array{0: Collection<int, Family>, 1: DoorScanData|null}
     */
    private function search(Church $church, string $query, bool $card): array
    {
        $read = $this->run(new GetDoorCardJob(church: $church, query: $query, card: $card));
        if ($read !== null && ! $read->searchToo) {
            return [$read->families, $read->scan];
        }

        $families = $this->run(new SearchFamiliesJob(church: $church, query: $query));

        // A typed word that is no child's card: just the families, as any search — the card's "no match" only when
        // nothing at all is found.
        return [$families, $read !== null && $families->isEmpty() ? $read->scan : null];
    }

    /**
     * The Level each found child with none would be suggested by their age — pre-selected when the door asks.
     *
     * @param  Collection<int, Family>  $families
     * @return array<int, int|null>
     */
    private function suggestedLevels(Collection $families): array
    {
        $suggested = [];
        foreach ($families as $family) {
            foreach ($family->children->filter(fn (Child $c): bool => $c->level === null) as $child) {
                $suggested[$child->id] = $this->run(new GetSuggestedLevelJob(church: $this->church(), dateOfBirth: $child->date_of_birth->toDateString()));
            }
        }

        return $suggested;
    }
}
