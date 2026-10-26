<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Church;
use App\Foundation\Jobs\BaseJob;
use Carbon\CarbonImmutable;

/**
 * Today's date at the church — in ITS timezone (churches.timezone, Asia/Dubai for CCF), at midnight. The door's
 * classes, and the Session a Check-in joins, are by this date; the app itself runs in UTC.
 *
 * `nxtgen.door_today` is set ONLY by E2EServiceProvider (APP_ENV=e2e): the browser journeys check children into
 * Saturday's 1PM on whatever day they run. No other environment sets it.
 *
 * @extends BaseJob<CarbonImmutable>
 */
final class GetChurchTodayJob extends BaseJob
{
    public function __construct(private readonly Church $church) {}

    public function handle(): CarbonImmutable
    {
        $fixed = config('nxtgen.door_today');
        if (is_string($fixed) && $fixed !== '') {
            return CarbonImmutable::parse($fixed, $this->church->timezone)->startOfDay();
        }

        return CarbonImmutable::now($this->church->timezone)->startOfDay();
    }
}
