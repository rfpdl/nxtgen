<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Home;

use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;

/**
 * The church's address opens the person's area (Roy 2026-11-06): an admin's is NxtGen Admin (the families list),
 * everyone else's NxtGen Serve (the door).
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Open the church home',
    persona: 'staff',
    happyPaths: ['story#42' => 'The church home opens an admin\'s Admin area and everyone else\'s Serve area'],
)]
#[AccessRow(AccessRowEnum::StaffArea)]
final class ShowChurchHomeFeature extends BaseChurchFeature
{
    public function handle(): RedirectResponse
    {
        return $this->scope()->canUseFullAdmin()
            ? redirect()->route('nxtgen.admin.families.index', $this->church())
            : redirect()->route('nxtgen.serve.door.show', $this->church());
    }
}
