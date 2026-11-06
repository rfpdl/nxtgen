<?php

declare(strict_types=1);

namespace App\Services\Church\Features\People;

use App\Domains\Auth\Jobs\CreateMagicLinkJob;
use App\Domains\Auth\Jobs\SendMagicLinkJob;
use App\Domains\Church\DataObjects\RoleScope;
use App\Domains\Church\DataObjects\StaffRoleData;
use App\Domains\Church\Jobs\CreateRoleAssignmentJob;
use App\Domains\Church\Jobs\GetSatelliteJob;
use App\Domains\Schedule\Jobs\GetScheduleJob;
use App\Domains\User\Jobs\CreateUserJob;
use App\Domains\User\Jobs\GetUserByEmailJob;
use App\Enums\AccessRowEnum;
use App\Enums\MagicLinkPurposeEnum;
use App\Enums\ScopeTypeEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Data\AddStaffInput;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Add someone who serves (D-R2). One login per person (plan §4): an existing login — often a parent — just
 * gains the role; otherwise a login is made. Then a sign-in link goes out saying what they were added as.
 * The role-grant ceiling is AccessScope::canGrant; anything above it is a 404, as the form never offers it.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Add someone to serve',
    persona: 'church admin or satellite admin',
    happyPaths: [
        'story#76' => 'An Admin adds someone: the login is made or reused, the Role given, a sign-in link emailed',
        'story#180' => 'An Admin makes someone class admin of a class they look after (TODO #9): a church admin any class, a Satellite admin one at their Satellite',
    ],
    unhappyPaths: [
        'story#77' => 'A Satellite admin gives only Lifeshaper, in their own Satellite; another church\'s Satellite is refused',
        'story#181' => 'Not at a class they do not look after, another church\'s, or a retired one; never Lifeshaper of a class',
    ],
)]
#[AccessRow(AccessRowEnum::GrantRoles)]
final class AddStaffFeature extends BaseChurchFeature
{
    public function handle(AddStaffInput $input): RedirectResponse
    {
        $church = $this->church();
        $scope = $this->scope();
        abort_unless($scope->canManagePeople(), 404);

        $satellite = $input->scope_type === ScopeTypeEnum::Satellite
            ? $this->run(new GetSatelliteJob(church: $church, id: $input->scope_id))
            : null;
        // A class admin (TODO #9): an active class of this church — its satellite says who may give it.
        $class = $input->scope_type === ScopeTypeEnum::Schedule
            ? $this->run(new GetScheduleJob(church: $church, id: $input->scope_id))
            : null;
        $place = $class === null ? $input->scope() : RoleScope::of($class);
        abort_unless($scope->canGrant($input->role, $place), 404);

        $label = StaffRoleData::label($input->role, $place, $class === null ? $satellite?->name : StaffRoleData::className($class));

        $result = DB::transaction(function () use ($church, $satellite, $class, $input) {
            $user = $this->run(new GetUserByEmailJob(email: $input->email))
                ?? $this->run(new CreateUserJob(name: $input->name, email: $input->email));

            $added = $this->run(new CreateRoleAssignmentJob(
                user: $user,
                role: $input->role,
                place: $class ?? $satellite ?? $church,
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
        if (! $this->run(new SendMagicLinkJob(issued: $issued, churchName: $church->name, invitedAs: $label))) {
            return back()->with('success', "{$user->email} is now {$label}. Too many emails went to them recently, so no sign-in link was sent — they can sign in from the sign-in page with this email.");
        }

        return back()->with('success', "{$user->email} is now {$label}. We emailed them a sign-in link.");
    }
}
