<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Guardian;
use App\Data\Models\User;
use App\Foundation\Jobs\BaseJob;

/**
 * The signed-in parent's own Guardian row, with its family and church — what RequireGuardian puts on the request for
 * `/family`. The address names no church (Roy 2026-11-07: the app is CCF's NxtGen only), so the church is read FROM
 * the row. A login has at most one family per church; were the data ever to hold two churches, the first.
 *
 * @extends BaseJob<Guardian|null>
 */
final class GetOwnGuardianJob extends BaseJob
{
    public function __construct(private readonly User $user) {}

    public function handle(): ?Guardian
    {
        return Guardian::query()->with('family.church')->where('user_id', $this->user->id)->orderBy('id')->first();
    }
}
