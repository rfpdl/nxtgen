<?php

declare(strict_types=1);

namespace App\Services\Family\Features\Child;

use App\Domains\Family\Jobs\GetFamilyChildJob;
use App\Domains\Family\Jobs\UpdateChildDetailsJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Inputs\ChildInput;
use App\Services\Family\Features\BaseFamilyFeature;
use Illuminate\Http\RedirectResponse;

/** @extends BaseFamilyFeature<RedirectResponse> */
#[AccessRow(AccessRowEnum::OwnFamily)]
final class UpdateChildFeature extends BaseFamilyFeature
{
    public function __construct(private readonly int $childId) {}

    public function handle(ChildInput $input): RedirectResponse
    {
        $family = $this->family();
        $child = $this->run(new GetFamilyChildJob(family: $family, childId: $this->childId));
        $this->run(new UpdateChildDetailsJob(child: $child, details: $input->toDetails()));

        return redirect()->route('family.show', $family->organization)->with('success', "{$child->displayName()}'s details are saved.");
    }
}
