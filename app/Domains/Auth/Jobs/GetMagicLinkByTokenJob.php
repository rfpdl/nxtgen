<?php

declare(strict_types=1);

namespace App\Domains\Auth\Jobs;

use App\Data\Models\MagicLink;
use App\Enums\MagicLinkPurposeEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * Read a link WITHOUT consuming it — used to find which email to lock on before redeeming a claim.
 *
 * @extends BaseJob<MagicLink|null>
 */
final class GetMagicLinkByTokenJob extends BaseJob
{
    public function __construct(
        private readonly string $token,
        private readonly MagicLinkPurposeEnum $purpose,
    ) {}

    public function handle(): ?MagicLink
    {
        return MagicLink::query()
            ->where('token_hash', hash('sha256', $this->token))
            ->where('purpose', $this->purpose)
            ->first();
    }
}
