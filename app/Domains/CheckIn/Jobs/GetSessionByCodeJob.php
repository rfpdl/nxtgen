<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\Jobs;

use App\Data\Models\Session;
use App\Foundation\Jobs\BaseJob;

/**
 * The Session a code names (spec item 16: `/<code>/checkin`), with its class and its satellite's church — or none.
 * The code is matched exactly (the column is case-sensitive). WHO may open it is RequireSessionAssignment's question.
 *
 * @extends BaseJob<Session|null>
 */
final class GetSessionByCodeJob extends BaseJob
{
    public function __construct(private readonly string $code) {}

    public function handle(): ?Session
    {
        return Session::query()->with(['schedule', 'satellite.church'])->where('code', $this->code)->first();
    }
}
