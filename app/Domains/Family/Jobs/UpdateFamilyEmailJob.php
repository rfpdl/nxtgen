<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Family;
use App\Data\Models\Note;
use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;
use App\Foundation\Support\EmailAddress;

/**
 * Staff correct a family's mistyped email (plan §4.4): the family's login gets the new address, unconfirmed, with
 * credential_version bumped (every link sent to the old address stops working) and the remember token cleared
 * (plan §4.3) — and the correction is noted on the family's history. The caller locks the login and checks it is
 * this family's alone.
 *
 * @extends BaseJob<User>
 */
final class UpdateFamilyEmailJob extends BaseJob
{
    public function __construct(
        private readonly Family $family,
        private readonly User $login,
        private readonly string $email,
        private readonly User $by,
    ) {}

    public function handle(): User
    {
        $from = $this->login->email;

        $this->login->forceFill([
            'email' => EmailAddress::normalise($this->email),
            'email_verified_at' => null,
            'credential_version' => $this->login->credential_version + 1,
            'remember_token' => null,
        ])->save();

        Note::system(about: $this->family, body: "Email corrected from {$from} to {$this->login->email}.", by: $this->by);

        return $this->login;
    }
}
