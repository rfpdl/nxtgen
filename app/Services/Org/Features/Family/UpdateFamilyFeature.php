<?php

declare(strict_types=1);

namespace App\Services\Org\Features\Family;

use App\Domains\Family\Jobs\GetFamilyByIdJob;
use App\Domains\Family\Jobs\UpdateFamilyDetailsJob;
use App\Domains\Family\Jobs\UpdateGuardianDetailsJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Inputs\FamilyDetailsInput;
use App\Services\Org\Features\BaseOrgFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Staff edit a family's details and its main parent's details (plan §3.2: church admin; satellite admin of the
 * family's home satellite). The same input and Jobs as the parent's own edit; the email has its own flow.
 *
 * @extends BaseOrgFeature<RedirectResponse>
 */
#[AccessRow(AccessRowEnum::EditFamily)]
final class UpdateFamilyFeature extends BaseOrgFeature
{
    public function __construct(private readonly int $familyId) {}

    public function handle(FamilyDetailsInput $input): RedirectResponse
    {
        $family = $this->run(new GetFamilyByIdJob(organizationId: $this->organization()->id, id: $this->familyId));
        abort_unless($this->scope()->canEditFamily($family), 404);
        $primary = $family->primaryGuardian;
        abort_if($primary === null, 404);

        DB::transaction(function () use ($family, $primary, $input): void {
            $this->run(new UpdateFamilyDetailsJob(family: $family, details: $input->toDetails()));
            $this->run(new UpdateGuardianDetailsJob(guardian: $primary, details: $input->guardian->toEdit()));
        });

        return back()->with('success', 'Family details saved.');
    }
}
