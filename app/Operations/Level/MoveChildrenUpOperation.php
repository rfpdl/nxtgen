<?php

declare(strict_types=1);

namespace App\Operations\Level;

use App\Data\Models\Church;
use App\Data\Models\MovingUp;
use App\Data\Models\User;
use App\Domains\Church\Jobs\GetChurchForUpdateJob;
use App\Domains\Family\Jobs\GetChurchChildrenJob;
use App\Domains\Family\Jobs\UpdateChildLevelJob;
use App\Domains\Family\Jobs\UpdateChildStatusJob;
use App\Domains\Level\Jobs\CreateMovingUpJob;
use App\Enums\ChildStatusEnum;
use App\Foundation\Support\LevelChart;
use Illuminate\Support\Facades\DB;
use Lucid\Units\Operation;

/**
 * Moving-Up (GLOSSARY; DECISIONS 2026-11-06): the children the church admin left ticked, each still Active at the
 * Level the page showed, go to the next Level — from the top Level they become Graduated — each noted on their
 * history, all kept as one Moving-Up so it can be put back.
 *
 * Exactly what the admin saw, or nothing: if any child has changed since (another admin, a double tap, a Moving-Up
 * just run), nothing moves and the answer is null. The church's row is locked first, so two presses run one after
 * the other and the second finds the children already moved. A child of another church is not found (404).
 *
 * @extends Operation<MovingUp|null>
 */
final class MoveChildrenUpOperation extends Operation
{
    /** @param array<int, int> $expected child id => the Level the page showed */
    public function __construct(
        private readonly Church $church,
        private readonly array $expected,
        private readonly User $by,
    ) {}

    public function handle(): ?MovingUp
    {
        return DB::transaction(function (): ?MovingUp {
            $this->run(new GetChurchForUpdateJob(church: $this->church));
            $children = $this->run(new GetChurchChildrenJob(church: $this->church, ids: array_keys($this->expected)));

            foreach ($children as $id => $child) {
                if ($child->status !== ChildStatusEnum::Active || $child->level !== $this->expected[$id]) {
                    return null;
                }
            }

            $movingUp = $this->run(new CreateMovingUpJob(church: $this->church, children: array_values($children), by: $this->by));

            foreach ($children as $child) {
                $next = LevelChart::movedUp((int) $child->level);
                $next === null
                    ? $this->run(new UpdateChildStatusJob(child: $child, status: ChildStatusEnum::Graduated, by: $this->by))
                    : $this->run(new UpdateChildLevelJob(child: $child, level: $next, by: $this->by));
            }

            return $movingUp;
        });
    }
}
