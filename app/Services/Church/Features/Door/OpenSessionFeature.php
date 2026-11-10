<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Door;

use App\Data\Models\Schedule;
use App\Domains\Church\Jobs\GetChurchTodayJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Operations\CheckIn\OpenSessionOperation;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;

/**
 * An admin opens a class for today (spec item 15): its Session is made, or opened again the same day. Only an active
 * class meeting today — never another day — of a satellite they look after, or their own class (a class admin).
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Open a Session',
    persona: 'satellite admin, class admin',
    happyPaths: ['story#202' => 'An admin (the satellite\'s, or that class\'s) opens today\'s class, and opens it again the same day once closed; Attendance lists every opened Session, 0 children included'],
    unhappyPaths: ['story#203' => 'Only on the class\'s date, only an admin of its satellite or of that class — never a Lifeshaper, another class\'s admin or another satellite\'s'],
)]
#[AccessRow(AccessRowEnum::OpenSession)]
final class OpenSessionFeature extends BaseChurchFeature
{
    public function __construct(private readonly Schedule $schedule) {}

    public function handle(): RedirectResponse
    {
        $class = $this->schedule;
        abort_unless($class->is_active && $this->scope()->canOpenSessionOf($class), 404);
        $today = $this->run(new GetChurchTodayJob(church: $this->church()));
        abort_unless($class->meetsOn($today), 404);

        $this->run(new OpenSessionOperation(schedule: $class, today: $today, by: $this->staff()));

        return back()->with('success', "{$class->name} is open.");
    }
}
