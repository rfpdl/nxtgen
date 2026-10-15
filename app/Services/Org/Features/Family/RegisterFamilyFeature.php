<?php

declare(strict_types=1);

namespace App\Services\Org\Features\Family;

use App\Domains\Organization\Jobs\GetBranchJob;
use App\Enums\RegistrationOutcomeEnum;
use App\Enums\RegistrationSourceEnum;
use App\Foundation\Inputs\RegisterFamilyInput;
use App\Operations\Registration\RegisterFamilyOperation;
use App\Services\Org\Features\BaseOrgFeature;
use Illuminate\Http\RedirectResponse;

/**
 * A volunteer registers a family for a parent with no internet (plan §7.2). Same single registration path;
 * the email stays unconfirmed until the parent taps the link, which never blocks check-in.
 *
 * @extends BaseOrgFeature<RedirectResponse>
 */
final class RegisterFamilyFeature extends BaseOrgFeature
{
    public function handle(RegisterFamilyInput $input): RedirectResponse
    {
        $org = $this->organization();
        abort_unless($this->scope()->canRegisterFamilies(), 404);

        $result = $this->run(new RegisterFamilyOperation($input->toRegistration(
            $org,
            $this->run(new GetBranchJob(organization: $org, id: $input->home_branch_id)),
            RegistrationSourceEnum::Staff,
            $this->staff(),
        )));

        $message = match ($result->outcome) {
            RegistrationOutcomeEnum::Registered => 'Registered. The parent will get an email to confirm. The children can check in now.',
            RegistrationOutcomeEnum::AlreadyRegistered => 'This email already has a family here — search for it and add the child instead.',
            RegistrationOutcomeEnum::ClaimSent, RegistrationOutcomeEnum::ClaimResent => 'Registered. The email already has an account, so the parent will get a link to connect this family.',
        };

        return redirect()->route('org.families.register', $org)->with('success', $message);
    }
}
