<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Serve;

use App\Data\Models\Schedule;
use App\Domains\CheckIn\DataObjects\ServeClassData;
use App\Domains\CheckIn\Jobs\GetSatelliteSessionsOnJob;
use App\Domains\CheckIn\Jobs\GetSessionsOnJob;
use App\Domains\Church\Jobs\GetChurchTodayJob;
use App\Domains\Schedule\Jobs\GetTodaysSchedulesJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\DataObjects\ServePageData;
use App\Services\Church\Features\BaseChurchFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Serve's first page (spec items 16–17): today, at the satellite in the address, the classes this person's Role covers
 * — each open one a link to its door at its own address (`/<code>/checkin`), each not open yet with "Open" for its
 * admin (spec item 15). Replaces the old door page, where a class was picked (Roy 2026-11-10).
 *
 * @extends BaseChurchFeature<Response>
 */
#[UserStory(
    title: 'See today\'s classes in Serve',
    persona: 'staff',
    happyPaths: [
        'story#208' => 'Staff see today\'s classes at their satellite — each open one a link to its door at its own address; an admin opens one from here',
    ],
    unhappyPaths: [
        'story#209' => 'Only the classes their Role covers at the satellite in the address — none of another satellite\'s, another day\'s or a retired one; no Open button for a Lifeshaper',
    ],
)]
#[AccessRow(AccessRowEnum::CheckIn)]
final class ShowServeFeature extends BaseChurchFeature
{
    public function handle(): Response
    {
        $church = $this->church();
        $scope = $this->scope();
        $today = $this->run(new GetChurchTodayJob(church: $church));
        $classes = $this->run(new GetTodaysSchedulesJob(church: $church, satelliteIds: $scope->doorSatelliteIds(), today: $today, alsoScheduleIds: $scope->classAdminScheduleIds()));
        $sessions = $this->run(new GetSessionsOnJob(classes: $classes, date: $today));
        // A class opened today stays listed though its day was changed since (Codex): its Session is today's.
        foreach ($this->run(new GetSatelliteSessionsOnJob(satellite: $this->satellite(), date: $today)) as $session) {
            if (! isset($sessions[$session->schedule_id]) && $scope->canCheckInTo($session->schedule)) {
                $classes[] = $session->schedule;
                $sessions[$session->schedule_id] = $session;
            }
        }
        usort($classes, fn (Schedule $a, Schedule $b): int => [$a->starts_at, mb_strtolower($a->name)] <=> [$b->starts_at, mb_strtolower($b->name)]);

        return Inertia::render('Church/Serve/Index', new ServePageData(
            today: $today->format('D j M'),
            classes: array_map(fn (Schedule $class): ServeClassData => ServeClassData::fromModel(
                $class,
                $sessions[$class->id] ?? null,
                $today,
                // Opening again needs the class to meet today (OpenSessionFeature).
                canOpen: $class->meetsOn($today) && $scope->canOpenSessionOf($class),
            ), $classes),
        ));
    }
}
