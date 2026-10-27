<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Door;

use App\Data\Models\CheckIn;
use App\Data\Models\Child;
use App\Data\Models\Family;
use App\Data\Models\Schedule;
use App\Domains\CheckIn\DataObjects\ChildHealthData;
use App\Domains\CheckIn\DataObjects\DoorCheckInData;
use App\Domains\CheckIn\DataObjects\DoorClassData;
use App\Domains\CheckIn\DataObjects\DoorFamilyData;
use App\Domains\CheckIn\Jobs\GetCheckedInChildrenJob;
use App\Domains\CheckIn\Jobs\GetSessionJob;
use App\Domains\Church\Jobs\GetChurchTodayJob;
use App\Domains\Family\Jobs\GetChurchChildJob;
use App\Domains\Family\Jobs\SearchFamiliesJob;
use App\Domains\Level\Jobs\GetLevelsJob;
use App\Domains\Level\Jobs\GetSuggestedLevelJob;
use App\Domains\Schedule\Jobs\GetTodaysSchedulesJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The door (DECISIONS 2026-10-26), phone first: today's date in the church's timezone; today's classes at the
 * satellites the viewer's Role covers (`?class=` picks one — the first by default); who is checked in to it, newest
 * first (DECISIONS 2026-10-27 — the page is never empty before a search; its length is the count); and the
 * church-wide family search (SearchFamiliesJob, the same one as registering — Q8) with each child's Level, Squad, an
 * allergy flag and whether they are in. No photo, no phone; a child's health details only when their name is tapped
 * (`?health=`, a partial reload of `health`).
 *
 * @extends BaseChurchFeature<Response>
 */
#[UserStory(
    title: 'Open the door',
    persona: 'Lifeshaper (any staff)',
    happyPaths: [
        'story#103' => 'Staff see today\'s classes at the satellites their Role covers, how many are checked in, and find any family of the church with each child\'s Level, Squad, allergy flag and whether they are in',
        'story#105' => 'Tapping a child\'s name shows their health details',
        'story#116' => 'Before and below any search, staff see who is checked in to the chosen class today, newest first — nickname, Squad and time — and can undo one from there',
    ],
    unhappyPaths: ['story#104' => 'Only today\'s classes at satellites their Role covers — another satellite\'s, another church\'s or another day\'s class is a 404; the search never carries a photo, a phone number or a health detail'],
)]
#[AccessRow(AccessRowEnum::CheckIn)]
final class ShowDoorFeature extends BaseChurchFeature
{
    public function handle(): Response
    {
        $church = $this->church();
        $today = $this->run(new GetChurchTodayJob(church: $church));
        $classes = $this->run(new GetTodaysSchedulesJob(church: $church, satelliteIds: $this->scope()->doorSatelliteIds(), today: $today));
        $class = $this->chosen($classes);

        $session = $class === null ? null : $this->run(new GetSessionJob(schedule: $class, date: $today));
        $checkIns = $session === null ? [] : $this->run(new GetCheckedInChildrenJob(session: $session));
        $checkedIn = [];   // child id => check-in id: each found child's ✓ and Undo
        foreach ($checkIns as $checkIn) {
            $checkedIn[$checkIn->child_id] = $checkIn->id;
        }

        $query = trim(request()->string('q')->value());
        $families = $this->run(new SearchFamiliesJob(church: $church, query: $query));
        $suggested = $this->suggestedLevels($families);

        return Inertia::render('Church/Door/Index', [
            'today' => $today->format('D j M'),
            'classes' => array_map(fn (Schedule $s): DoorClassData => DoorClassData::fromModel($s), $classes),
            'class_id' => $class?->id,
            'checked_in' => array_map(fn (CheckIn $c): DoorCheckInData => DoorCheckInData::fromCheckIn($c, $church->timezone), $checkIns),
            'query' => $query,
            'families' => array_values($families->map(fn (Family $f): DoorFamilyData => DoorFamilyData::fromModel($f, $checkedIn, $suggested))->all()),
            'levels' => $this->run(new GetLevelsJob),
            'health' => Inertia::optional(fn (): ?ChildHealthData => $class === null || ! request()->filled('health')
                ? null
                : ChildHealthData::fromModel($this->run(new GetChurchChildJob(church: $church, id: request()->integer('health'))))),
        ]);
    }

    /**
     * The class `?class=` names, which must be one of today's offered to this viewer (a 404 otherwise); else the first.
     *
     * @param  list<Schedule>  $classes
     */
    private function chosen(array $classes): ?Schedule
    {
        if (! request()->filled('class')) {
            return $classes[0] ?? null;
        }

        $id = request()->integer('class');
        foreach ($classes as $class) {
            if ($class->id === $id) {
                return $class;
            }
        }

        abort(404);
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
