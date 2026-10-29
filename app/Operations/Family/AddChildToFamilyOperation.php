<?php

declare(strict_types=1);

namespace App\Operations\Family;

use App\Data\Models\Child;
use App\Data\Models\Family;
use App\Domains\Card\Jobs\CreateFirstCardRequestJob;
use App\Domains\Family\DataObjects\ChildDetailsData;
use App\Domains\Family\Jobs\CreateChildJob;
use App\Domains\Family\Jobs\CreateDuplicateCandidatesJob;
use App\Domains\Level\Jobs\GetSuggestedLevelJob;
use App\Enums\RegistrationSourceEnum;
use Illuminate\Support\Facades\DB;
use Lucid\Units\Operation;

/**
 * Add a child to an existing family (parent page, or staff after a search) — same Level suggestion and
 * duplicate check as a full registration (plan §2), and the same free first name card request (name cards 02).
 *
 * @extends Operation<Child>
 */
final class AddChildToFamilyOperation extends Operation
{
    public function __construct(
        private readonly Family $family,
        private readonly ChildDetailsData $details,
        private readonly RegistrationSourceEnum $source,
        private readonly ?int $level = null,
    ) {}

    public function handle(): Child
    {
        return DB::transaction(function (): Child {
            // A reviewed import placement wins (plan §5); otherwise the child's age suggests the Level.
            $level = $this->level ?? $this->run(new GetSuggestedLevelJob(church: $this->family->church, dateOfBirth: $this->details->dateOfBirth));

            $child = $this->run(new CreateChildJob(family: $this->family, details: $this->details, level: $level, source: $this->source));
            $this->run(new CreateFirstCardRequestJob(child: $child, source: $this->source));
            $this->run(new CreateDuplicateCandidatesJob(family: $this->family));

            return $child;
        });
    }
}
