<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Foundation\Jobs\BaseJob;

/**
 * A child in this church with the same first name, last name and birthday — used by the import so a row that
 * was corrected in the sheet (new source key) is never inserted a second time.
 *
 * @extends BaseJob<Child|null>
 */
final class GetMatchingChildJob extends BaseJob
{
    public function __construct(
        private readonly Church $church,
        private readonly string $firstName,
        private readonly string $lastName,
        private readonly string $dateOfBirth,
    ) {}

    public function handle(): ?Child
    {
        return Child::query()
            ->where('church_id', $this->church->id)
            ->whereRaw('LOWER(first_name) = ?', [mb_strtolower(trim($this->firstName))])
            ->whereRaw('LOWER(last_name) = ?', [mb_strtolower(trim($this->lastName))])
            ->whereDate('date_of_birth', $this->dateOfBirth)
            ->first();
    }
}
