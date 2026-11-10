<?php

declare(strict_types=1);

namespace App\Services\Church\Features\People;

use App\Data\Models\Church;
use App\Data\Models\Schedule;
use App\Data\Models\Session;
use App\Domains\CheckIn\Jobs\GetSessionsByIdJob;
use App\Domains\Church\DataObjects\RoleScope;
use App\Domains\Church\DataObjects\StaffRoleData;
use App\Domains\Church\Jobs\GetChurchTodayJob;
use App\Domains\Church\Jobs\GetSatelliteJob;
use App\Domains\Schedule\Jobs\GetScheduleJob;
use App\Enums\AccessRowEnum;
use App\Enums\ScopeTypeEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Operations\Church\GiveRoleOperation;
use App\Services\Church\Data\AddStaffInput;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;

/**
 * Add someone who serves (D-R2). One login per person (plan §4): an existing login — often a parent — just
 * gains the role; otherwise a login is made. Then a sign-in link goes out saying what they were added as.
 * The role-grant ceiling is AccessScope::canGrant; anything above it is a 404, as the form never offers it.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Add someone to serve',
    persona: 'satellite admin',
    happyPaths: [
        'story#76' => 'An Admin adds someone: the login is made or reused, the Role given, a sign-in link emailed',
        'story#180' => 'An Admin makes someone class admin of a class at their Satellite (TODO #9)',
        'story#211' => 'An Admin adds a helper for the day (spec item 18) on People, for one of their satellite\'s classes open now; the helper is emailed a sign-in link',
    ],
    unhappyPaths: [
        'story#77' => 'A Satellite admin gives a Role only at their own Satellite; another Satellite or another church\'s is refused',
        'story#181' => 'Not at a class they do not look after, another church\'s, or a retired one; never Lifeshaper of a class',
        'story#212' => 'A helper only for a class open now at their own satellite — not one closed or not opened, not another satellite\'s; never a helper for a whole satellite, nor another Role for one day; a Lifeshaper or class admin adds none',
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

        // At a satellite, a class admin at an active class (TODO #9), or a helper for the day at a Session open now
        // (spec item 18) — whose satellite says who may give it.
        $at = match ($input->scope_type) {
            ScopeTypeEnum::Schedule => $this->run(new GetScheduleJob(church: $church, id: $input->scope_id)),
            ScopeTypeEnum::Session => $this->openSession($church, $input->scope_id),
            ScopeTypeEnum::Satellite => $this->run(new GetSatelliteJob(church: $church, id: $input->scope_id)),
        };
        $place = RoleScope::of($at);
        abort_unless($scope->canGrant($input->role, $place), 404);

        $label = StaffRoleData::label($input->role, $place, match (true) {
            $at instanceof Schedule => StaffRoleData::className($at),
            $at instanceof Session => StaffRoleData::sessionName($at),
            default => $at->name,
        });

        [$level, $message] = $this->run(new GiveRoleOperation(
            church: $church, name: $input->name, email: $input->email, role: $input->role, place: $at, label: $label, by: $this->staff(),
        ));

        return back()->with($level, $message);
    }

    /** A Session of this church open now — a helper for the day is for a class that is on (Claude's call, issue 14). */
    private function openSession(Church $church, int $id): Session
    {
        $session = $this->run(new GetSessionsByIdJob(church: $church, ids: [$id]))[$id] ?? abort(404);
        abort_unless($session->isOpenOn($this->run(new GetChurchTodayJob(church: $church))), 404);

        return $session;
    }
}
