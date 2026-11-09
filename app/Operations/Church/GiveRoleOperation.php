<?php

declare(strict_types=1);

namespace App\Operations\Church;

use App\Data\Models\Church;
use App\Data\Models\Satellite;
use App\Data\Models\Schedule;
use App\Data\Models\User;
use App\Domains\Auth\Jobs\CreateMagicLinkJob;
use App\Domains\Auth\Jobs\SendMagicLinkJob;
use App\Domains\Church\Jobs\CreateRoleAssignmentJob;
use App\Domains\User\Jobs\CreateUserJob;
use App\Domains\User\Jobs\GetUserByEmailJob;
use App\Enums\MagicLinkPurposeEnum;
use App\Enums\RoleEnum;
use Illuminate\Support\Facades\DB;
use Lucid\Units\Operation;

/**
 * Give someone a Role (People, spec item 9; the platform's People, spec item 12): the login with that email, or a new
 * one, gets the Role — a removed one is switched back on — and an email with a sign-in link saying what they were added
 * as. The caller decided who may give it. Answers the flash to show: `success` or `error`, and its words.
 *
 * Replies name the email that was typed, never the account's own name: an existing login's name is not the giver's
 * to learn (SECURITY #16).
 *
 * @extends Operation<array{0: 'success'|'error', 1: string}>
 */
final class GiveRoleOperation extends Operation
{
    public function __construct(
        private readonly Church $church,
        private readonly string $name,
        private readonly string $email,
        private readonly RoleEnum $role,
        private readonly Satellite|Schedule $place,
        private readonly string $label,
        private readonly User $by,
    ) {}

    /** @return array{0: 'success'|'error', 1: string} */
    public function handle(): array
    {
        [$user, $added] = DB::transaction(function (): array {
            $user = $this->run(new GetUserByEmailJob(email: $this->email))
                ?? $this->run(new CreateUserJob(name: $this->name, email: $this->email));

            return [$user, $this->run(new CreateRoleAssignmentJob(user: $user, role: $this->role, place: $this->place, grantedBy: $this->by))];
        });

        if (! $added) {
            return ['error', "{$user->email} is already {$this->label}."];
        }

        $issued = $this->run(new CreateMagicLinkJob(purpose: MagicLinkPurposeEnum::Login, email: $user->email, user: $user));
        if (! $this->run(new SendMagicLinkJob(issued: $issued, churchName: $this->church->name, invitedAs: $this->label))) {
            return ['success', "{$user->email} is now {$this->label}. Too many emails went to them recently, so no sign-in link was sent — they can sign in from the sign-in page with this email."];
        }

        return ['success', "{$user->email} is now {$this->label}. We emailed them a sign-in link."];
    }
}
