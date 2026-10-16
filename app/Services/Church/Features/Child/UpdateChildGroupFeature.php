<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Child;

use App\Domains\Family\Jobs\CreateSystemNoteJob;
use App\Domains\Family\Jobs\GetChurchChildJob;
use App\Domains\Family\Jobs\UpdateChildGroupJob;
use App\Domains\Group\Jobs\GetGroupJob;
use App\Enums\AccessRowEnum;
use App\Enums\GenderEnum;
use App\Foundation\Attributes\AccessRow;
use App\Services\Church\Data\UpdateChildGroupInput;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A church admin corrects a child's group outside Moving-Up (plan §3.2). The group must be this church's and fit
 * the child (a "Boys" group is not for a girl). Noted on the child's history.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[AccessRow(AccessRowEnum::SetChildGroup)]
final class UpdateChildGroupFeature extends BaseChurchFeature
{
    public function __construct(private readonly int $childId) {}

    public function handle(UpdateChildGroupInput $input): RedirectResponse
    {
        abort_unless($this->scope()->canSetGroup(), 404);
        $church = $this->church();
        $child = $this->run(new GetChurchChildJob(churchId: $church->id, childId: $this->childId));

        $group = $this->run(new GetGroupJob(churchId: $church->id, id: $input->group_id));
        if ($group === null) {
            throw ValidationException::withMessages(['group_id' => 'Please choose one of this church\'s groups.']);
        }
        if ($group->gender !== null && $group->gender !== $child->gender) {
            $for = $group->gender === GenderEnum::Boy ? 'boys' : 'girls';
            throw ValidationException::withMessages(['group_id' => "{$group->name} is for {$for}."]);
        }

        $from = $child->group->name ?? 'no group';
        DB::transaction(function () use ($child, $group, $from): void {
            $this->run(new UpdateChildGroupJob(child: $child, group: $group, by: $this->staff()));
            $this->run(new CreateSystemNoteJob(about: $child, body: "{$child->displayName()}: group changed from {$from} to {$group->name}.", by: $this->staff()));
        });

        return back()->with('success', "{$child->displayName()} is now in {$group->name}.");
    }
}
