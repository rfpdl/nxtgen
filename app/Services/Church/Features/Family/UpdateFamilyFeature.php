<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Family;

use App\Domains\Family\Jobs\CreateDuplicateCandidatesJob;
use App\Domains\Family\Jobs\UpdateFamilyDetailsJob;
use App\Domains\Family\Jobs\UpdateFamilyGuardiansJob;
use App\Domains\Family\Jobs\UpdateGuardianDetailsJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Foundation\Inputs\FamilyDetailsInput;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Staff edit a family's details and its main parent's details (plan §3.2: church admin; satellite admin of the
 * family's home satellite). The same input and Jobs as the parent's own edit; the email has its own flow. The
 * family's other guardians are changed as the form says, and a phone that came in or changed is checked for possible
 * duplicates.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Edit a family',
    persona: 'church admin or satellite admin',
    happyPaths: [
        'story#52' => 'An Admin who may edit the Family edits it and its parent',
        'story#89' => 'An Admin adds, changes and removes the Family\'s other Guardians, each change on its history',
    ],
    unhappyPaths: [
        'story#53' => 'A Lifeshaper or another Satellite\'s Admin cannot',
        'story#90' => 'The primary Guardian, or another Family\'s, is never changed through the list',
    ],
)]
#[AccessRow(AccessRowEnum::EditFamily)]
final class UpdateFamilyFeature extends BaseChurchFeature
{
    public function __construct(private readonly int $familyId) {}

    public function handle(FamilyDetailsInput $input): RedirectResponse
    {
        $family = $this->scopedFamily($this->familyId, $this->scope()->canEditFamily(...));
        $primary = $family->primaryGuardian;
        abort_if($primary === null, 404);

        DB::transaction(function () use ($family, $primary, $input): void {
            $this->run(new UpdateFamilyDetailsJob(family: $family, details: $input->toDetails()));
            $phones = $this->run(new UpdateGuardianDetailsJob(guardian: $primary, details: $input->guardian->toEdit()))->wasChanged('phone');
            if ($input->changesOtherGuardians()) {
                $phones = $this->run(new UpdateFamilyGuardiansJob(family: $family, guardians: $input->otherGuardians(), removedIds: $input->removed_guardian_ids, by: $this->staff())) || $phones;
            }
            // A phone came in or changed: it may match another family's.
            if ($phones) {
                $this->run(new CreateDuplicateCandidatesJob(family: $family));
            }
        });

        return back()->with('success', 'Family details saved.');
    }
}
