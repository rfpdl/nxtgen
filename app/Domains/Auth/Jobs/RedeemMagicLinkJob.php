<?php

declare(strict_types=1);

namespace App\Domains\Auth\Jobs;

use App\Data\Models\MagicLink;
use App\Enums\FamilyClaimStatusEnum;
use App\Enums\MagicLinkPurposeEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * Redeem a link exactly once (plan §4.3).
 *
 * The binding check and the consumption are ONE conditional UPDATE joined to the user (or claim): the link is
 * consumed only if it is unused, unexpired, AND the account's email + credential version (or the claim's status
 * + revision + email) still match what the link was issued for. A correction landing between a separate check
 * and consume could otherwise let an old-address link through (Codex diff review, high). Two simultaneous
 * clicks still cannot both succeed: only one UPDATE affects the row.
 *
 * Returns null for anything invalid; callers show one generic message.
 *
 * @extends BaseJob<MagicLink|null>
 */
final class RedeemMagicLinkJob extends BaseJob
{
    public function __construct(
        private readonly string $token,
        private readonly MagicLinkPurposeEnum $purpose,
    ) {}

    public function handle(): ?MagicLink
    {
        $link = MagicLink::query()
            ->where('token_hash', hash('sha256', $this->token))
            ->where('purpose', $this->purpose)
            ->first();

        if ($link === null) {
            return null;
        }

        $consume = MagicLink::query()
            ->whereKey($link->id)
            ->whereNull('magic_links.used_at')
            ->where('magic_links.expires_at', '>', now());

        if ($link->user_id !== null) {
            $consume->join('users', 'users.id', '=', 'magic_links.user_id')
                ->whereColumn('users.email', 'magic_links.email_snapshot')
                ->whereColumn('users.credential_version', 'magic_links.credential_version');
        } elseif ($link->family_claim_id !== null) {
            $consume->join('family_claims', 'family_claims.id', '=', 'magic_links.family_claim_id')
                ->where('family_claims.status', FamilyClaimStatusEnum::Pending->value)
                ->whereColumn('family_claims.revision', 'magic_links.claim_revision')
                ->whereColumn('family_claims.email_normalised', 'magic_links.email_snapshot');
        } else {
            return null;
        }

        $consumed = $consume->update(['magic_links.used_at' => now()]);

        return $consumed === 1 ? $link->refresh()->load(['user', 'familyClaim']) : null;
    }
}
