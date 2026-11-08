<?php

declare(strict_types=1);

namespace App\Services\Church\Features\MovingUp;

use App\Data\Models\Child;
use App\Domains\Level\DataObjects\MovingUpCandidateData;
use App\Domains\Level\DataObjects\MovingUpData;
use App\Domains\Level\Jobs\GetChildrenWithoutLevelCountJob;
use App\Domains\Level\Jobs\GetLastMovingUpJob;
use App\Domains\Level\Jobs\GetMovingUpChildrenJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Features\BaseChurchFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Moving-Up (TODO #27; DECISIONS 2026-11-06): every child who will move — the church's Active children with a Level,
 * lowest Level first — each with where they go, for the admin to untick anyone who stays; how many Active
 * children have no Level (not moved); and the last Moving-Up, to put back.
 *
 * @extends BaseChurchFeature<Response>
 */
#[UserStory(
    title: 'See Moving-Up',
    persona: 'satellite admin',
    happyPaths: ['story#154' => 'A Satellite admin sees every child of their satellite who will move up, with their new Level, how many have no Level, and the last Moving-Up with who was put back'],
    unhappyPaths: ['story#155' => 'Not there for a Lifeshaper (sent to Serve) or another satellite\'s admin'],
)]
#[AccessRow(AccessRowEnum::MovingUp)]
final class ShowMovingUpFeature extends BaseChurchFeature
{
    public function handle(): Response
    {
        abort_unless($this->scope()->canMoveUp(), 404);
        $church = $this->church();
        $satellite = $this->satellite();
        $last = $this->run(new GetLastMovingUpJob(satellite: $satellite));

        return Inertia::render('Church/MovingUp/Index', [
            'children' => array_map(
                fn (Child $c): MovingUpCandidateData => MovingUpCandidateData::fromChild($c),
                $this->run(new GetMovingUpChildrenJob(satellite: $satellite)),
            ),
            'without_level' => $this->run(new GetChildrenWithoutLevelCountJob(satellite: $satellite)),
            'last' => $last === null ? null : MovingUpData::fromModel($last, $church->timezone),
        ]);
    }
}
