<?php

declare(strict_types=1);

namespace App\Services\Org\Features\Child;

use App\Domains\Family\Jobs\GetOrganizationChildJob;
use App\Domains\Family\Jobs\UpdateChildStatusJob;
use App\Services\Org\Data\UpdateChildStatusInput;
use App\Services\Org\Features\BaseOrgFeature;
use Illuminate\Http\RedirectResponse;

/**
 * Active / inactive / graduated — church admin, or admin of the family's home satellite (plan §3.2).
 *
 * @extends BaseOrgFeature<RedirectResponse>
 */
final class UpdateChildStatusFeature extends BaseOrgFeature
{
    public function __construct(private readonly int $childId) {}

    public function handle(UpdateChildStatusInput $input): RedirectResponse
    {
        $child = $this->run(new GetOrganizationChildJob(organizationId: $this->organization()->id, childId: $this->childId));
        abort_unless($this->scope()->canEditFamily($child->family), 404);

        $this->run(new UpdateChildStatusJob(child: $child, status: $input->status, by: $this->staff()));

        return back()->with('success', "{$child->displayName()} is now {$input->status->label()}.");
    }
}
