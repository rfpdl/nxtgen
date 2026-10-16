<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Family;

use App\Domains\Church\Jobs\GetSatelliteJob;
use App\Enums\AccessRowEnum;
use App\Enums\RegistrationOutcomeEnum;
use App\Enums\RegistrationSourceEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Foundation\Inputs\RegisterFamilyInput;
use App\Operations\Registration\RegisterFamilyOperation;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;

/**
 * A staff member registers a family for a parent with no internet (plan §7.2). Same single registration path;
 * the email stays unconfirmed until the parent taps the link, which never blocks check-in.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Register a family for a parent',
    persona: 'staff',
    happyPaths: ['story#47' => 'Staff register a Family for a parent, email unconfirmed, recording who did it'],
)]
#[AccessRow(AccessRowEnum::RegisterFamily)]
final class RegisterFamilyFeature extends BaseChurchFeature
{
    public function handle(RegisterFamilyInput $input): RedirectResponse
    {
        $church = $this->church();
        abort_unless($this->scope()->canRegisterFamilies(), 404);

        $result = $this->run(new RegisterFamilyOperation($input->toRegistration(
            $church,
            $this->run(new GetSatelliteJob(church: $church, id: $input->home_satellite_id)),
            RegistrationSourceEnum::Staff,
            $this->staff(),
        )));

        $message = match ($result->outcome) {
            RegistrationOutcomeEnum::Registered => 'Registered. The parent will get an email to confirm. The children can check in now.',
            RegistrationOutcomeEnum::AlreadyRegistered => 'This email already has a family here — search for it and add the child instead.',
            RegistrationOutcomeEnum::ClaimSent, RegistrationOutcomeEnum::ClaimResent => 'Registered. The email already has an account, so the parent will get a link to connect this family.',
        };

        return redirect()->route('church.families.register', $church)->with('success', $message);
    }
}
