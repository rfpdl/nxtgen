<?php

declare(strict_types=1);

namespace App\Services\Church\Features\MovingUp;

use App\Data\Models\MovingUp;
use App\Data\Models\MovingUpChild;
use App\Enums\AccessRowEnum;
use App\Enums\PutBackResultEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Operations\Level\PutBackChildOperation;
use Illuminate\Http\RedirectResponse;

/**
 * "Put everyone back" (DECISIONS 2026-11-06): every child of the last Moving-Up not yet put back, each as "Put back"
 * would (PutBackChildOperation), all together. Children someone changed since are left as they are and named.
 *
 * @extends BaseMovingUpFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Put everyone back',
    persona: 'satellite admin',
    happyPaths: ['story#160' => 'A Satellite admin puts back every child of their satellite\'s last Moving-Up not yet put back, and is told who was left because they changed since'],
    unhappyPaths: ['story#161' => 'Only the satellite\'s last Moving-Up; never by a Lifeshaper or another satellite\'s admin'],
)]
#[AccessRow(AccessRowEnum::MovingUp)]
final class PutBackEveryoneFeature extends BaseMovingUpFeature
{
    public function __construct(private readonly MovingUp $movingUp) {}

    public function handle(): RedirectResponse
    {
        [$putBack, $left] = $this->withLastMovingUp($this->movingUp, function (MovingUp $movingUp): array {
            $waiting = $movingUp->children->filter(fn (MovingUpChild $m): bool => $m->put_back_at === null);
            $putBack = 0;
            $left = [];
            foreach ($waiting as $moved) {
                $result = $this->run(new PutBackChildOperation(satellite: $this->satellite(), movingUp: $movingUp, childId: $moved->child_id, by: $this->staff()));
                match ($result) {
                    PutBackResultEnum::PutBack => $putBack++,
                    PutBackResultEnum::ChangedSince => $left[] = $moved->child->displayName(),
                    // Already back, or another satellite's now (its family moved): not this satellite's to name.
                    PutBackResultEnum::AlreadyPutBack, PutBackResultEnum::Elsewhere => null,
                };
            }

            return [$putBack, $left];
        });

        if ($putBack === 0 && $left === []) {
            return back()->with('success', 'Everyone was already put back.');
        }

        return back()->with($putBack === 0 ? 'error' : 'success', ($putBack === 1 ? '1 child' : "{$putBack} children").' put back.'
            .($left === [] ? '' : ' Left as they are (changed since Moving-Up): '.implode(', ', $left).'.'));
    }
}
