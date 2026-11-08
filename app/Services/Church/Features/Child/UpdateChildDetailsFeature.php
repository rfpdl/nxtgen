<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Child;

use App\Data\Models\Child;
use App\Domains\Family\Jobs\UpdateChildDetailsJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Foundation\Inputs\ChildInput;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;

/**
 * Staff fix a child's details (plan §3.2: who may edit the child's family). Not status or Level — status is
 * its own action, the Level changes only at Moving-Up (or by a satellite admin's correction). The same input and Job as the parent's own edit.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Fix a child\'s details',
    persona: 'staff',
    happyPaths: ['story#60' => 'Staff who can open the Family — a Lifeshaper of its Satellite too — fix a Child\'s details, but not the status'],
    unhappyPaths: ['story#61' => 'A Child outside the editor\'s Satellites is refused'],
)]
#[AccessRow(AccessRowEnum::EditFamily)]
final class UpdateChildDetailsFeature extends BaseChurchFeature
{
    public function __construct(private readonly Child $child) {}

    public function handle(ChildInput $input): RedirectResponse
    {
        $child = $this->scopedChild($this->child, $this->scope()->canEditFamilyDetails(...));

        $this->run(new UpdateChildDetailsJob(child: $child, details: $input->toDetails()));

        return back()->with('success', "{$child->displayName()}'s details are saved.");
    }
}
