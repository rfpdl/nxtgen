<?php

declare(strict_types=1);

namespace App\Services\Org\Features\Child;

use App\Domains\Family\Jobs\CreateSystemNoteJob;
use App\Domains\Family\Jobs\GetOrganizationChildJob;
use App\Domains\Family\Jobs\UpdateChildStatusJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Services\Org\Data\UpdateChildStatusInput;
use App\Services\Org\Features\BaseOrgFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Active / inactive / graduated — church admin, or admin of the family's home satellite (plan §3.2).
 *
 * @extends BaseOrgFeature<RedirectResponse>
 */
#[AccessRow(AccessRowEnum::SetChildStatus)]
final class UpdateChildStatusFeature extends BaseOrgFeature
{
    public function __construct(private readonly int $childId) {}

    public function handle(UpdateChildStatusInput $input): RedirectResponse
    {
        $child = $this->run(new GetOrganizationChildJob(organizationId: $this->organization()->id, childId: $this->childId));
        abort_unless($this->scope()->canEditFamily($child->family), 404);

        $from = $child->status;

        DB::transaction(function () use ($child, $from, $input): void {
            $this->run(new UpdateChildStatusJob(child: $child, status: $input->status, by: $this->staff()));
            if ($from !== $input->status) {
                $this->run(new CreateSystemNoteJob(
                    about: $child,
                    body: "{$child->displayName()}: status changed from {$from->label()} to {$input->status->label()}.",
                    by: $this->staff(),
                ));
            }
        });

        return back()->with('success', "{$child->displayName()} is now {$input->status->label()}.");
    }
}
