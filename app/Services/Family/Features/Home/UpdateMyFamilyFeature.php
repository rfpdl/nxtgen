<?php

declare(strict_types=1);

namespace App\Services\Family\Features\Home;

use App\Data\Models\User;
use App\Domains\Family\Jobs\GetGuardianForUserJob;
use App\Domains\Family\Jobs\UpdateFamilyDetailsJob;
use App\Domains\Family\Jobs\UpdateGuardianDetailsJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Inputs\FamilyDetailsInput;
use App\Services\Family\Features\BaseFamilyFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * A parent edits their own family's details and their OWN parent details (plan §3.2, review B5). The family is
 * the signed-in parent's, resolved by RequireGuardian — there is no id in the request to point elsewhere.
 *
 * @extends BaseFamilyFeature<RedirectResponse>
 */
#[AccessRow(AccessRowEnum::OwnFamily)]
final class UpdateMyFamilyFeature extends BaseFamilyFeature
{
    public function handle(FamilyDetailsInput $input): RedirectResponse
    {
        $family = $this->family();
        $user = request()->user();
        abort_unless($user instanceof User, 404);
        $me = $this->run(new GetGuardianForUserJob(user: $user, church: $family->church));
        abort_if($me === null, 404);

        DB::transaction(function () use ($family, $me, $input): void {
            $this->run(new UpdateFamilyDetailsJob(family: $family, details: $input->toDetails()));
            $this->run(new UpdateGuardianDetailsJob(guardian: $me, details: $input->guardian->toEdit()));
        });

        return redirect()->route('family.show', $family->church)->with('success', 'Your family details are saved.');
    }
}
