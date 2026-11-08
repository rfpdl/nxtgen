<?php

declare(strict_types=1);

namespace App\Services\Family\Features\Child;

use App\Data\Models\Child;
use App\Domains\Family\Jobs\UpdateChildDetailsJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Foundation\Inputs\ChildInput;
use App\Services\Family\Features\BaseFamilyFeature;
use Illuminate\Http\RedirectResponse;

/** @extends BaseFamilyFeature<RedirectResponse> */
#[UserStory(
    title: 'Edit my child\'s details',
    persona: 'parent',
    happyPaths: ['story#34' => 'A parent edits their Child\'s details, but not the status or Level'],
    unhappyPaths: ['story#35' => 'A Child of another Family is refused'],
)]
#[AccessRow(AccessRowEnum::OwnFamily)]
final class UpdateChildFeature extends BaseFamilyFeature
{
    public function __construct(private readonly Child $child) {}

    public function handle(ChildInput $input): RedirectResponse
    {
        $family = $this->family();
        $child = $this->ownChild($this->child);
        $this->run(new UpdateChildDetailsJob(child: $child, details: $input->toDetails()));

        return redirect()->route('family.show')->with('success', "{$child->displayName()}'s details are saved.");
    }
}
