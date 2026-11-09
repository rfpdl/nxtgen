<?php

declare(strict_types=1);

namespace App\Services\Platform\Features\People;

use App\Data\Models\Schedule;
use App\Domains\Church\DataObjects\RoleScope;
use App\Domains\Church\DataObjects\StaffRoleData;
use App\Domains\Church\Jobs\GetSatelliteJob;
use App\Domains\Schedule\Jobs\GetScheduleJob;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use App\Foundation\Attributes\UserStory;
use App\Operations\Church\GiveRoleOperation;
use App\Services\Platform\Data\GiveRoleInput;
use App\Services\Platform\Features\BasePlatformFeature;
use Illuminate\Http\RedirectResponse;

/**
 * Give a Role anywhere (spec item 12): Admin or Lifeshaper at any satellite of the church, Admin at any of its active
 * classes — how a new satellite gets its first admin. The person gets a sign-in link (GiveRoleOperation).
 *
 * @extends BasePlatformFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Give a Role anywhere',
    persona: 'platform admin',
    happyPaths: ['story#198' => 'The platform admin gives any Role at any Satellite or class — a new Satellite\'s first admin too'],
)]
final class GiveRoleFeature extends BasePlatformFeature
{
    public function handle(GiveRoleInput $input): RedirectResponse
    {
        $church = $this->church();
        $at = $input->scope_type === ScopeTypeEnum::Schedule
            ? $this->run(new GetScheduleJob(church: $church, id: $input->scope_id))
            : $this->run(new GetSatelliteJob(church: $church, id: $input->scope_id));
        // At a class, only the Admin Role (a class admin, TODO #9).
        abort_if($at instanceof Schedule && $input->role !== RoleEnum::Admin, 404);
        $label = StaffRoleData::label($input->role, RoleScope::of($at), $at instanceof Schedule ? StaffRoleData::className($at) : $at->name);

        [$level, $message] = $this->run(new GiveRoleOperation(
            church: $church, name: $input->name, email: $input->email, role: $input->role, place: $at, label: $label, by: $this->platformAdmin(),
        ));

        return back()->with($level, $message);
    }
}
