<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Family;

use App\Domains\Family\Jobs\GetFamilyByIdJob;
use App\Enums\AccessRowEnum;
use App\Enums\RegistrationSourceEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Foundation\Inputs\ChildInput;
use App\Operations\Family\AddChildToFamilyOperation;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;

/**
 * Any staff in the church may add a child to a family found by search — registration is church-wide
 * (plan §3.2), even when the family's home satellite is another one.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Add a child to a found family',
    persona: 'staff',
    happyPaths: ['story#48' => 'Any Staff member adds a Child to a Family found by search, at any Satellite'],
    unhappyPaths: ['story#49' => 'A Family of another church is refused'],
)]
#[AccessRow(AccessRowEnum::AddChild)]
final class AddChildFeature extends BaseChurchFeature
{
    public function __construct(private readonly int $familyId) {}

    public function handle(ChildInput $input): RedirectResponse
    {
        abort_unless($this->scope()->canRegisterFamilies(), 404);
        $family = $this->run(new GetFamilyByIdJob(church: $this->church(), id: $this->familyId));

        $child = $this->run(new AddChildToFamilyOperation(family: $family, details: $input->toDetails(), source: RegistrationSourceEnum::Staff));

        return back()->with('success', "{$child->displayName()} is added to the family.");
    }
}
