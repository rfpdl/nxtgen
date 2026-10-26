<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Door;

use App\Domains\Church\Jobs\GetChurchTodayJob;
use App\Domains\Family\Jobs\GetChurchChildrenJob;
use App\Domains\Schedule\Jobs\GetScheduleJob;
use App\Enums\AccessRowEnum;
use App\Enums\CheckInResultEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Operations\CheckIn\CheckInChildOperation;
use App\Services\Church\Data\CheckInChildInput;
use App\Services\Church\Data\CheckInInput;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;

/**
 * "Check in N children" at the door (DECISIONS 2026-10-26): the ticked children of a family, into a class meeting
 * today at a satellite the viewer's Role covers. Any child of the church may be checked in — a visitor joins THIS
 * satellite's Squad. Each child has its own result (CheckInChildOperation): the page says who is in and, apart, who
 * was not and why.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Check children in',
    persona: 'Lifeshaper (any staff)',
    happyPaths: [
        'story#106' => 'Staff tick a family\'s children and check them in to today\'s class; the first Check-in makes the Session, and each child joins this satellite\'s Squad for their Level and gender — a visiting child too; a child already in stays in once',
        'story#107' => 'An inactive child is made active again first, and a child with no Level gets one picked at the door; both are saved and noted on the child\'s history',
    ],
    unhappyPaths: [
        'story#108' => 'A Graduated child is refused with the reason; an inactive child not made active, and a child with no Level, are not checked in',
        'story#109' => 'Only a class meeting today at a satellite their Role covers, only this church\'s children, at least one child and a Level from the chart',
    ],
)]
#[AccessRow(AccessRowEnum::CheckIn)]
final class CheckInChildrenFeature extends BaseChurchFeature
{
    public function __construct(private readonly int $scheduleId) {}

    public function handle(CheckInInput $input): RedirectResponse
    {
        $church = $this->church();
        $class = $this->run(new GetScheduleJob(church: $church, id: $this->scheduleId));
        abort_unless($this->scope()->canCheckInAt($class->satellite), 404);
        $today = $this->run(new GetChurchTodayJob(church: $church));
        abort_unless($class->meetsOn($today), 404);

        $children = $this->run(new GetChurchChildrenJob(church: $church, ids: array_map(fn (CheckInChildInput $c): int => $c->child_id, $input->children)));

        $results = [];
        foreach ($input->children as $ticked) {
            $child = $children[$ticked->child_id];
            $results[$child->displayName()] = $this->run(new CheckInChildOperation(
                schedule: $class,
                today: $today,
                child: $child,
                level: $ticked->level,
                makeActive: $ticked->make_active,
                by: $this->staff(),
            ));
        }

        return $this->said(back(), $results);
    }

    /**
     * "Checked in Amara and Ben. Noah was already checked in." as the success, and the children who were not checked
     * in, each with the reason, as the error.
     *
     * @param  array<string, CheckInResultEnum>  $results  child's name => what happened
     */
    private function said(RedirectResponse $response, array $results): RedirectResponse
    {
        $in = $already = $refused = [];
        foreach ($results as $name => $result) {
            match ($result) {
                CheckInResultEnum::CheckedIn => $in[] = $name,
                CheckInResultEnum::AlreadyIn => $already[] = $name,
                default => $refused[] = "{$name} {$result->label()}",
            };
        }

        $success = [];
        if ($in !== []) {
            $success[] = 'Checked in '.Arr::join($in, ', ', ' and ').'.';
        }
        if ($already !== []) {
            $success[] = Arr::join($already, ', ', ' and ').(count($already) === 1 ? ' was' : ' were').' already checked in.';
        }

        if ($success !== []) {
            $response = $response->with('success', implode(' ', $success));
        }

        return $refused === [] ? $response : $response->with('error', 'Not checked in: '.implode('; ', $refused).'.');
    }
}
