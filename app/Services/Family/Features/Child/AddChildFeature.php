<?php

declare(strict_types=1);

namespace App\Services\Family\Features\Child;

use App\Enums\AccessRowEnum;
use App\Enums\RegistrationSourceEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Foundation\Inputs\ChildInput;
use App\Operations\Family\AddChildToFamilyOperation;
use App\Services\Family\Features\BaseFamilyFeature;
use Illuminate\Http\RedirectResponse;

/** @extends BaseFamilyFeature<RedirectResponse> */
#[UserStory(
    title: 'Add a child to my family',
    persona: 'parent',
    happyPaths: ['story#32' => 'A parent adds a Child, at the Level their age suggests'],
    unhappyPaths: ['story#33' => 'A new Child is checked, in plain words'],
)]
#[AccessRow(AccessRowEnum::OwnFamily)]
final class AddChildFeature extends BaseFamilyFeature
{
    public function handle(ChildInput $input): RedirectResponse
    {
        $family = $this->family();
        $child = $this->run(new AddChildToFamilyOperation(family: $family, details: $input->toDetails(), source: RegistrationSourceEnum::Self));

        return redirect()->route('family.show', $family->church)->with('success', "{$child->displayName()} is added to your family.");
    }
}
