<?php

declare(strict_types=1);

namespace App\Services\Family\Features\Home;

use App\Domains\Family\Jobs\CreateDuplicateCandidatesJob;
use App\Domains\Family\Jobs\UpdateFamilyDetailsJob;
use App\Domains\Family\Jobs\UpdateFamilyGuardiansJob;
use App\Domains\Family\Jobs\UpdateGuardianDetailsJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Foundation\Inputs\FamilyDetailsInput;
use App\Services\Family\Features\BaseFamilyFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * A parent edits their own family's details and their OWN parent details (plan §3.2, review B5). The family is
 * the signed-in parent's, resolved by RequireGuardian — there is no id in the request to point elsewhere. The
 * family's other guardians are changed as the form says (no logins; DECISIONS 2026-10-17, after first look), and a
 * phone that came in or changed is checked for possible duplicates.
 *
 * @extends BaseFamilyFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Edit my family',
    persona: 'parent',
    happyPaths: [
        'story#30' => 'A parent edits their Family details and their own',
        'story#91' => 'A parent adds, changes and removes their Family\'s other Guardians, each change on its history',
    ],
    unhappyPaths: [
        'story#31' => 'A Satellite of another church is refused, and no other Family changes',
        'story#94' => 'Neither the parent themselves nor another Family\'s Guardian is changed through the list',
    ],
)]
#[AccessRow(AccessRowEnum::OwnFamily)]
final class UpdateMyFamilyFeature extends BaseFamilyFeature
{
    public function handle(FamilyDetailsInput $input): RedirectResponse
    {
        $family = $this->family();
        $me = $this->guardian();

        DB::transaction(function () use ($family, $me, $input): void {
            $this->run(new UpdateFamilyDetailsJob(family: $family, details: $input->toDetails()));
            $phones = $this->run(new UpdateGuardianDetailsJob(guardian: $me, details: $input->guardian->toEdit()))->wasChanged('phone');
            if ($input->changesOtherGuardians()) {
                $phones = $this->run(new UpdateFamilyGuardiansJob(family: $family, guardians: $input->otherGuardians(), removedIds: $input->removed_guardian_ids, by: $this->guardianUser())) || $phones;
            }
            // A phone came in or changed: it may match another family's.
            if ($phones) {
                $this->run(new CreateDuplicateCandidatesJob(family: $family));
            }
        });

        return redirect()->route('family.show', $family->church)->with('success', 'Your family details are saved.');
    }
}
