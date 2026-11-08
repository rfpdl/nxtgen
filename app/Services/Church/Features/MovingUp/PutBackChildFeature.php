<?php

declare(strict_types=1);

namespace App\Services\Church\Features\MovingUp;

use App\Data\Models\Child;
use App\Data\Models\MovingUp;
use App\Data\Models\MovingUpChild;
use App\Enums\AccessRowEnum;
use App\Enums\PutBackResultEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Foundation\Support\LevelChart;
use App\Operations\Level\PutBackChildOperation;
use Illuminate\Http\RedirectResponse;

/**
 * "Put back" one child of the last Moving-Up (Roy 2026-11-06: "can we undo specific student as well?"): back to the
 * Level and status they had, unless someone changed them since (PutBackChildOperation).
 *
 * @extends BaseMovingUpFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Put one child back',
    persona: 'church admin',
    happyPaths: ['story#158' => 'A Church admin puts one child of the last Moving-Up back to the Level (and status) they had'],
    unhappyPaths: ['story#159' => 'A child changed since is left as they are, and the admin is told; only the last Moving-Up; never by a Satellite admin'],
)]
#[AccessRow(AccessRowEnum::MovingUp)]
final class PutBackChildFeature extends BaseMovingUpFeature
{
    public function __construct(
        private readonly MovingUp $movingUp,
        private readonly Child $child,
    ) {}

    public function handle(): RedirectResponse
    {
        [$moved, $result] = $this->withLastMovingUp($this->movingUp, function (MovingUp $movingUp): array {
            $moved = $movingUp->children->first(fn (MovingUpChild $m): bool => $m->child_id === $this->child->id);
            abort_unless($moved instanceof MovingUpChild, 404);

            return [$moved, $this->run(new PutBackChildOperation(church: $this->church(), movingUp: $movingUp, childId: $this->child->id, by: $this->staff()))];
        });
        $name = $moved->child->displayName();

        return match ($result) {
            PutBackResultEnum::PutBack => back()->with('success', "{$name} {$result->label()}: ".LevelChart::name($moved->from_level).'.'),
            PutBackResultEnum::AlreadyPutBack => back()->with('success', "{$name} {$result->label()}."),
            PutBackResultEnum::ChangedSince => back()->with('error', "{$name} {$result->label()}."),
        };
    }
}
