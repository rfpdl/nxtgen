<?php

declare(strict_types=1);

namespace App\Services\Public\Features\Registration;

use App\Data\Models\Satellite;
use App\Domains\Church\Jobs\GetSatelliteJob;
use App\Enums\RegistrationSourceEnum;
use App\Foundation\Attributes\UserStory;
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
#[UserStory(
    title: 'Register my family',
    persona: 'parent',
    happyPaths: [
        'story#4' => 'A parent registers their Family and Children and is thanked',
        'story#87' => 'A parent adds the Family\'s other parents and Guardians, who get no login: the Family has one',
        'story#184' => 'The lifestage is Single, Married or KSP, or none given',
    ],
    unhappyPaths: [
        'story#5' => 'An email already registered gets the same answer, so nobody learns who is registered',
        'story#6' => 'A Satellite of another church is refused',
        'story#185' => 'Any other lifestage is refused',
    ],
)]
final class RegisterFamilyFeature extends BasePublicFeature
{
    public function __construct(private readonly Satellite $satellite) {}

    public function handle(RegisterFamilyInput $input): RedirectResponse
    {
        $church = $this->satellite->church;
        $home = $this->run(new GetSatelliteJob(church: $church, id: $input->home_satellite_id));
        $result = $this->run(new RegisterFamilyOperation($input->toRegistration($church, $home, RegistrationSourceEnum::Self)));

        return redirect()->route('public.register.thanks', $this->satellite)->with('success', $result->outcome->guardianMessage());
    }
}
