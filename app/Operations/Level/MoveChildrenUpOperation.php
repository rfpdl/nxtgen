<?php

declare(strict_types=1);

namespace App\Operations\Level;

use App\Data\Models\MovingUp;
use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Domains\Church\Jobs\GetChurchForUpdateJob;
use App\Domains\Family\Jobs\GetChurchChildrenJob;
use App\Domains\Family\Jobs\GetFamilyForUpdateJob;
use App\Domains\Family\Jobs\UpdateChildLevelJob;
use App\Domains\Family\Jobs\UpdateChildStatusJob;
use App\Domains\Level\Jobs\CreateMovingUpJob;
use App\Enums\ChildStatusEnum;
use App\Foundation\Support\LevelChart;
use Illuminate\Support\Facades\DB;
use Lucid\Units\Operation;

/**
 * Moving-Up (GLOSSARY; DECISIONS 2026-11-06): the children the satellite's admin left ticked (Moving-Up per satellite, DECISIONS 2026-11-08), each still Active at the
 * Level the page showed, go to the next Level — from the top Level they become Graduated — each noted on their
 * history, all kept as one Moving-Up so it can be put back.
 *
 * Exactly what the admin saw, or nothing: if any child has changed since (another admin, a double tap, a Moving-Up
 * just run, a family moved to another satellite), nothing moves and the answer is null. The church's row is locked first, so two presses run one after
 * the other and the second finds the children already moved. A child of another church is not found (404); one of another satellite moves nobody.
 *
 * @extends Operation<MovingUp|null>
 */
final class MoveChildrenUpOperation extends Operation
{
    /** @param array<int, int> $expected child id => the Level the page showed */
    public function __construct(
        private readonly Satellite $satellite,
        private readonly array $expected,
        private readonly User $by,
    ) {}

    public function handle(): ?MovingUp
    {
        // The church read BEFORE the transaction: a first read inside it would fix the snapshot before the lock, and the
        // children would be read as they were before a Level fix that committed meanwhile (issue 06 review).
        $church = $this->satellite->church;

        return DB::transaction(function () use ($church): ?MovingUp {
            $this->run(new GetChurchForUpdateJob(church: $church));
            $children = $this->run(new GetChurchChildrenJob(church: $church, ids: array_keys($this->expected)));

            foreach ($children as $id => $child) {
                // Each family still this satellite's, its row locked so a move to another satellite waits (issue 06
                // review): a child of another satellite — or one moving there now — and nobody moves.
                $family = $this->run(new GetFamilyForUpdateJob(family: $child->family));
                if ($family->home_satellite_id !== $this->satellite->id
                    || $child->status !== ChildStatusEnum::Active || $child->level !== $this->expected[$id]) {
                    return null;
                }
            }

            $movingUp = $this->run(new CreateMovingUpJob(satellite: $this->satellite, children: array_values($children), by: $this->by));

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
