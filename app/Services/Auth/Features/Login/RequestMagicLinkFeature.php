<?php

declare(strict_types=1);

namespace App\Services\Auth\Features\Login;

use App\Domains\Auth\Jobs\CreateMagicLinkJob;
use App\Domains\Auth\Jobs\SendMagicLinkJob;
use App\Domains\Auth\Jobs\SendNoAccountEmailJob;
use App\Domains\User\Jobs\GetUserByEmailJob;
use App\Enums\MagicLinkPurposeEnum;
use App\Foundation\Attributes\UserStory;
use App\Services\Auth\Data\RequestMagicLinkInput;
use App\Services\Auth\Features\BaseAuthFeature;
use Illuminate\Http\RedirectResponse;

/**
 * Same answer for every email (no account oracle, plan §4.3). A known email gets a 2-hour sign-in link;
 * an unknown one gets a "no account — ask for the registration link" note, so only the inbox owner learns.
 *
 * @extends BaseAuthFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Ask for a sign-in link',
    persona: 'anyone',
    happyPaths: ['story#9' => 'A known email gets a sign-in link and an unknown one a register-here note, with the same answer on screen'],
    unhappyPaths: ['story#10' => 'Repeated requests for one email are slowed down'],
)]
final class RequestMagicLinkFeature extends BaseAuthFeature
{
    public function handle(RequestMagicLinkInput $input): RedirectResponse
    {
        $user = $this->run(new GetUserByEmailJob(email: $input->email));

        if ($user === null) {
            $this->run(new SendNoAccountEmailJob(email: $input->email));
        } else {
            $issued = $this->run(new CreateMagicLinkJob(
                purpose: MagicLinkPurposeEnum::Login,
                email: $user->email,
                user: $user,
                ip: request()->ip(),
                userAgent: (string) request()->userAgent(),
            ));
            $this->run(new SendMagicLinkJob(issued: $issued, churchName: config()->string('app.name')));
        }

        return redirect()->route('login')->with('success', 'If this email is registered, we have sent you a sign-in link.');
    }
}
