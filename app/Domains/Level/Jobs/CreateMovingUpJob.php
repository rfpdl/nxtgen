<?php

declare(strict_types=1);

namespace App\Domains\Level\Jobs;

use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Data\Models\MovingUp;
use App\Data\Models\User;
use App\Enums\ChildStatusEnum;
use App\Foundation\Jobs\BaseJob;
use App\Foundation\Support\LevelChart;
use Illuminate\Database\Eloquent\Collection;

/**
 * Keep a Moving-Up: these children, each with their Level and status now and after it (the next Level; from the top
 * Level the same Level and Graduated), saying who ran it. It comes back with its children. Run BEFORE the children change. The caller checks who may
 * and runs it in a transaction with the changes.
 *
 * @extends BaseJob<MovingUp>
 */
final class CreateMovingUpJob extends BaseJob
{
    /** @param list<Child> $children Active, each with a Level */
    public function __construct(
        private readonly Church $church,
        private readonly array $children,
        private readonly User $by,
    ) {}

    public function handle(): MovingUp
    {
        $movingUp = MovingUp::query()->create(['church_id' => $this->church->id, 'made_by' => $this->by->id]);
        $moved = new Collection;

        foreach ($this->children as $child) {
            $from = (int) $child->level;
            $next = LevelChart::movedUp($from);
            $moved->push($movingUp->children()->create([
                'church_id' => $this->church->id,
                'child_id' => $child->id,
                'from_level' => $from,
                'from_status' => $child->status,
                'to_level' => $next ?? $from,
                'to_status' => $next === null ? ChildStatusEnum::Graduated : $child->status,
            ]));
        }

        return $movingUp->setRelation('children', $moved);
    }
}
