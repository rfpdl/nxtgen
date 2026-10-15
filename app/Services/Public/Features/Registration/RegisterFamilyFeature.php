<?php

declare(strict_types=1);

namespace App\Services\Public\Features\Registration;

use App\Data\Models\Organization;
use App\Domains\Organization\Jobs\GetBranchJob;
use App\Enums\RegistrationSourceEnum;
use App\Foundation\Inputs\RegisterFamilyInput;
use App\Operations\Registration\RegisterFamilyOperation;
use App\Services\Public\Features\BasePublicFeature;
use Illuminate\Http\RedirectResponse;

/**
 * A parent registers their own family (plan §6 R1). Everything goes through the one registration path; the
 * public answer is the same for every outcome (no membership oracle).
 *
 * @extends BasePublicFeature<RedirectResponse>
 */
final class RegisterFamilyFeature extends BasePublicFeature
{
    public function __construct(private readonly Organization $organization) {}

    public function handle(RegisterFamilyInput $input): RedirectResponse
    {
        $branch = $this->run(new GetBranchJob(organization: $this->organization, id: $input->home_branch_id));
        $result = $this->run(new RegisterFamilyOperation($input->toRegistration($this->organization, $branch, RegistrationSourceEnum::Self)));

        return redirect()->route('public.register.thanks', $this->organization)->with('success', $result->outcome->parentMessage());
    }
}
