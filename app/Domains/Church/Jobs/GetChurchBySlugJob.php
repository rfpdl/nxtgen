<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Church;
use App\Foundation\Jobs\BaseJob;

/**
 * The church a terminal command names by its slug (`--church=ccf`), or null — there is no address, so no binding.
 *
 * @extends BaseJob<Church|null>
 */
final class GetChurchBySlugJob extends BaseJob
{
    public function __construct(private readonly string $slug) {}

    public function handle(): ?Church
    {
        return Church::query()->where('slug', $this->slug)->first();
    }
}
