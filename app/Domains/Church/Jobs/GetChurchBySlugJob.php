<?php

declare(strict_types=1);

namespace App\Domains\Church\Jobs;

use App\Data\Models\Church;
use App\Foundation\Jobs\BaseJob;

/**
 * The church a setting names (`nxtgen.church.slug`), read as the lowercase slug it is stored as — or null when there
 * is none (an empty setting, or a database not seeded yet), so the page leaves the link out instead of failing.
 *
 * @extends BaseJob<Church|null>
 */
final class GetChurchBySlugJob extends BaseJob
{
    public function __construct(private readonly string $slug) {}

    public function handle(): ?Church
    {
        $slug = strtolower(trim($this->slug));

        return $slug === '' ? null : Church::query()->where('slug', $slug)->first();
    }
}
