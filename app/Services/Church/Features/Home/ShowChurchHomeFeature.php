<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Home;

use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;

/**
 * Release 1's dashboard is the registration list.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[AccessRow(AccessRowEnum::StaffArea)]
final class ShowChurchHomeFeature extends BaseChurchFeature
{
    public function handle(): RedirectResponse
    {
        return redirect()->route('church.families.index', $this->church());
    }
}
