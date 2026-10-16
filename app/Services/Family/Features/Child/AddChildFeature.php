<?php

declare(strict_types=1);

namespace App\Services\Family\Features\Child;

use App\Enums\AccessRowEnum;
use App\Enums\RegistrationSourceEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Inputs\ChildInput;
use App\Operations\Family\AddChildToFamilyOperation;
use App\Services\Family\Features\BaseFamilyFeature;
use Illuminate\Http\RedirectResponse;

/** @extends BaseFamilyFeature<RedirectResponse> */
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
