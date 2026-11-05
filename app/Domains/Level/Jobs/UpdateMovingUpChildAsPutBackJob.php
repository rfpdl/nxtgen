<?php

declare(strict_types=1);

namespace App\Domains\Level\Jobs;

use App\Data\Models\MovingUpChild;
use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;

/**
 * Mark a child of a Moving-Up put back, saying who and when. The caller puts the child's Level and status back.
 *
 * @extends BaseJob<MovingUpChild>
 */
final class UpdateMovingUpChildAsPutBackJob extends BaseJob
{
    public function __construct(
        private readonly MovingUpChild $moved,
        private readonly User $by,
    ) {}

    public function handle(): MovingUpChild
    {
        $this->moved->forceFill(['put_back_at' => now(), 'put_back_by' => $this->by->id])->save();

        return $this->moved;
    }
}
