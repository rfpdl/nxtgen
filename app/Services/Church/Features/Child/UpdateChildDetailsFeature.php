<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Child;

use App\Domains\Family\Jobs\GetChurchChildJob;
use App\Domains\Family\Jobs\UpdateChildDetailsJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Inputs\ChildInput;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;

/**
 * Staff fix a child's details (plan §3.2: who may edit the child's family). Not status or Level — status is
 * its own action, the Level changes only at Moving-Up (or by a church admin's correction). The same input and Job as the parent's own edit.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[AccessRow(AccessRowEnum::EditFamily)]
final class UpdateChildDetailsFeature extends BaseChurchFeature
{
    public function __construct(private readonly int $childId) {}

    public function handle(ChildInput $input): RedirectResponse
    {
        $child = $this->run(new GetChurchChildJob(churchId: $this->church()->id, childId: $this->childId));
        abort_unless($this->scope()->canEditFamily($child->family), 404);

        $this->run(new UpdateChildDetailsJob(child: $child, details: $input->toDetails()));

        return back()->with('success', "{$child->displayName()}'s details are saved.");
    }
}
