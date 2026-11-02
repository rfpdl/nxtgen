<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Church;
use App\Foundation\Jobs\BaseJob;

/**
 * Every church in the app, oldest first — for work the terminal does church by church (the photo sweep), so each
 * church's rows are then read within that church.
 *
 * @extends BaseJob<list<Church>>
 */
final class GetChurchesJob extends BaseJob
{
    /** @return list<Church> */
    public function handle(): array
    {
        return array_values(Church::query()->orderBy('id')->get()->all());
    }
}
