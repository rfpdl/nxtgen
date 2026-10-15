<?php

declare(strict_types=1);

namespace App\Services\Org\Features\Home;

use App\Services\Org\Features\BaseOrgFeature;
use Illuminate\Http\RedirectResponse;

/**
 * Release 1's dashboard is the registration list.
 *
 * @extends BaseOrgFeature<RedirectResponse>
 */
final class ShowOrgHomeFeature extends BaseOrgFeature
{
    public function handle(): RedirectResponse
    {
        return redirect()->route('org.families.index', $this->organization());
    }
}
