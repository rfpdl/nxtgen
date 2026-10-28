<?php

declare(strict_types=1);

namespace App\Domains\Schedule\Jobs;

use App\Data\Models\Schedule;
use App\Foundation\Jobs\BaseJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * The class the door's `?class=` names, which must be one of `$classes` (today's, offered to this viewer — a 404
 * otherwise); with no id, the first of them, or null when none meets today. Shared by the door's two tabs, Check in
 * and Who's in, so both pick a class by the same rules.
 *
 * @extends BaseJob<Schedule|null>
 */
final class GetChosenScheduleJob extends BaseJob
{
    /** @param list<Schedule> $classes */
    public function __construct(
        private readonly array $classes,
        private readonly ?int $id,
    ) {}

    public function handle(): ?Schedule
    {
        if ($this->id === null) {
            return $this->classes[0] ?? null;
        }

        foreach ($this->classes as $class) {
            if ($class->id === $this->id) {
                return $class;
            }
        }

        throw (new ModelNotFoundException)->setModel(Schedule::class, [$this->id]);
    }
}
