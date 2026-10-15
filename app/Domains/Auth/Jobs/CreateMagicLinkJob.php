<?php

declare(strict_types=1);

namespace App\Domains\Auth\Jobs;

use App\Data\Models\FamilyClaim;
use App\Data\Models\MagicLink;
use App\Data\Models\User;
use App\Domains\Auth\DataObjects\IssuedMagicLinkData;
use App\Enums\MagicLinkPurposeEnum;
use App\Foundation\Jobs\BaseJob;
use Illuminate\Support\Str;

/**
 * Issue a single-use link (plan §4.3). Login and verify links bind to the user's email + credential
 * version; claim links bind to the claim's revision. Login: 2 hours (Roy). Verify / claim: 7 days.
 *
 * @extends BaseJob<IssuedMagicLinkData>
 */
final class CreateMagicLinkJob extends BaseJob
{
    public function __construct(
        private readonly MagicLinkPurposeEnum $purpose,
        private readonly string $email,
        private readonly ?User $user = null,
        private readonly ?FamilyClaim $claim = null,
        private readonly ?string $ip = null,
        private readonly ?string $userAgent = null,
    ) {}

    public function handle(): IssuedMagicLinkData
    {
        $token = Str::random(48);

        $link = MagicLink::query()->create([
            'user_id' => $this->user?->id,
            'email_snapshot' => Str::lower(trim($this->email)),
            'purpose' => $this->purpose,
            'family_claim_id' => $this->claim?->id,
            'claim_revision' => $this->claim?->revision,
            'credential_version' => $this->user?->credential_version,
            'token_hash' => hash('sha256', $token),
            'expires_at' => $this->purpose === MagicLinkPurposeEnum::Login ? now()->addHours(2) : now()->addDays(7),
            'ip' => $this->ip,
            'user_agent' => $this->userAgent,
        ]);

        return new IssuedMagicLinkData(token: $token, link: $link);
    }
}
