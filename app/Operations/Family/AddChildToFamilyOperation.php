<?php

declare(strict_types=1);

namespace App\Operations\Family;

use App\Data\Models\Child;
use App\Data\Models\Family;
use App\Data\Models\Group;
use App\Domains\Family\DataObjects\ChildDetailsData;
use App\Domains\Family\Jobs\CreateChildJob;
use App\Domains\Family\Jobs\CreateDuplicateCandidatesJob;
use App\Domains\Group\Jobs\GetSuggestedGroupJob;
use App\Enums\RegistrationSourceEnum;
use Illuminate\Support\Facades\DB;
use Lucid\Units\Operation;

/**
 * Add a child to an existing family (parent page, or staff after a search) — same group suggestion and
 * duplicate check as a full registration (plan §2).
 *
 * @extends Operation<Child>
 */
final class AddChildToFamilyOperation extends Operation
{
    public function __construct(
        private readonly Family $family,
        private readonly ChildDetailsData $details,
        private readonly RegistrationSourceEnum $source,
        private readonly ?Group $group = null,
    ) {}

    public function handle(): Child
    {
        return DB::transaction(function (): Child {
            // A reviewed import placement wins (plan §5); otherwise suggest from age + gender.
            $group = $this->group ?? $this->run(new GetSuggestedGroupJob(
                church: $this->family->church,
                gender: $this->details->gender,
                dateOfBirth: $this->details->dateOfBirth,
            ));

            $child = $this->run(new CreateChildJob(family: $this->family, details: $this->details, group: $group, source: $this->source));
            $this->run(new CreateDuplicateCandidatesJob(family: $this->family));

            return $child;
        });
    }
}
