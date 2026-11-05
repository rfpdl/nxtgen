<?php

declare(strict_types=1);

namespace App\Services\Church\Features\MovingUp;

use App\Data\Models\MovingUpChild;
use App\Enums\AccessRowEnum;
use App\Enums\ChildStatusEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Operations\Level\MoveChildrenUpOperation;
use App\Services\Church\Data\MoveChildrenUpInput;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;

/**
 * "Move up N children" (DECISIONS 2026-11-06): the children the church admin left ticked go to the next Level, from
 * the top Level to Graduated (MoveChildrenUpOperation). If any changed since the page was opened, nobody moves and
 * the admin is asked to look again.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Move children up',
    persona: 'church admin',
    happyPaths: ['story#156' => 'A Church admin moves the ticked children up a Level — from the top Level to Graduated — and the unticked stay'],
    unhappyPaths: ['story#157' => 'Nobody moves when a child changed since the page was opened, or none is ticked; never another church\'s child; never by a Satellite admin'],
)]
#[AccessRow(AccessRowEnum::MovingUp)]
final class MoveChildrenUpFeature extends BaseChurchFeature
{
    public function handle(MoveChildrenUpInput $input): RedirectResponse
    {
        abort_unless($this->scope()->canMoveUp(), 404);

        $movingUp = $this->run(new MoveChildrenUpOperation(church: $this->church(), expected: $input->expected(), by: $this->staff()));
        if ($movingUp === null) {
            return back()->with('error', 'Some children changed since this page was opened, so nobody moved. Please look again.');
        }

        $moved = $movingUp->children->count();
        $graduated = $movingUp->children->filter(fn (MovingUpChild $m): bool => $m->to_status === ChildStatusEnum::Graduated)->count();

        return back()->with('success', ($moved === 1 ? '1 child' : "{$moved} children").' moved up'.($graduated > 0 ? "; {$graduated} Graduated." : '.'));
    }
}
