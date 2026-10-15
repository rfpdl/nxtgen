<?php

declare(strict_types=1);

namespace App\Services\Org\Features\People;

use App\Domains\Auth\Jobs\CreateMagicLinkJob;
use App\Domains\Auth\Jobs\SendMagicLinkJob;
use App\Domains\Organization\DataObjects\StaffRoleData;
use App\Domains\Organization\Jobs\CreateRoleAssignmentJob;
use App\Domains\Organization\Jobs\GetBranchJob;
use App\Domains\User\Jobs\CreateUserJob;
use App\Domains\User\Jobs\GetUserByEmailJob;
use App\Enums\AccessRowEnum;
use App\Enums\MagicLinkPurposeEnum;
use App\Enums\ScopeTypeEnum;
use App\Foundation\Attributes\AccessRow;
use App\Services\Org\Data\AddStaffInput;
use App\Services\Org\Features\BaseOrgFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Add someone who serves (D-R2). One login per person (plan §4): an existing login — often a parent — just
 * gains the role; otherwise a login is made. Then a sign-in link goes out saying what they were added as.
 * The role-grant ceiling is AccessScope::canGrant; anything above it is a 404, as the form never offers it.
 *
 * @extends BaseOrgFeature<RedirectResponse>
 */
#[AccessRow(AccessRowEnum::GrantRoles)]
final class AddStaffFeature extends BaseOrgFeature
{
    public function handle(AddStaffInput $input): RedirectResponse
    {
        $org = $this->organization();
        $scope = $this->scope();
        abort_unless($scope->canManagePeople(), 404);

        $branchName = $input->scope_type === ScopeTypeEnum::Branch
            ? $this->run(new GetBranchJob(organization: $org, id: $input->scope_id))->name
            : null;
        abort_unless($scope->canGrant($input->role, $input->scope_type, $input->scope_id), 404);

        $label = StaffRoleData::label($input->role, $input->scope_type, $branchName);

        $result = DB::transaction(function () use ($org, $input) {
            $user = $this->run(new GetUserByEmailJob(email: $input->email))
                ?? $this->run(new CreateUserJob(name: $input->name, email: $input->email));

            $added = $this->run(new CreateRoleAssignmentJob(
                organization: $org,
                user: $user,
                role: $input->role,
                scopeType: $input->scope_type,
                scopeId: $input->scope_id,
                grantedBy: $this->staff(),
            ));

            return ['user' => $user, 'added' => $added];
        });

        // Replies name the email the admin typed, never the account's own name: an existing login's name is
        // not the admin's to learn from this form (SECURITY #16).
        $user = $result['user'];
        if (! $result['added']) {
            return back()->with('error', "{$user->email} is already {$label}.");
        }

        $issued = $this->run(new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::Login, email: $user->email, user: $user));
        if (! $this->run(new SendMagicLinkJob(issued: $issued, churchName: $org->name, invitedAs: $label))) {
            return back()->with('success', "{$user->email} is now {$label}. Too many emails went to them recently, so no sign-in link was sent — they can sign in from the sign-in page with this email.");
        }

        return back()->with('success', "{$user->email} is now {$label}. We emailed them a sign-in link.");
    }
}
