<?php

declare(strict_types=1);

namespace App\Operations\CheckIn;

use App\Data\Models\Schedule;
use App\Data\Models\Session;
use App\Data\Models\User;
use App\Domains\CheckIn\Jobs\CreateSessionJob;
use App\Domains\CheckIn\Jobs\GetSessionForUpdateJob;
use App\Domains\CheckIn\Jobs\UpdateSessionAsOpenJob;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Lucid\Units\Operation;

/**
 * An admin opens a class on `$today` (spec item 15): its Session for the day is made open — or, closed earlier today,
 * opened again: the same Session, so its Check-ins stay. The caller checks the class meets today.
 *
 * @extends Operation<Session>
 */
final class OpenSessionOperation extends Operation
{
    public function __construct(
        private readonly Schedule $schedule,
        private readonly CarbonImmutable $today,
        private readonly User $by,
    ) {}

    public function handle(): Session
    {
        return DB::transaction(function (): Session {
            $session = $this->run(new GetSessionForUpdateJob(
                session: $this->run(new CreateSessionJob(schedule: $this->schedule, date: $this->today, by: $this->by)),
            ));

            return $session->closed_at === null ? $session : $this->run(new UpdateSessionAsOpenJob(session: $session));
        });
    }
}
