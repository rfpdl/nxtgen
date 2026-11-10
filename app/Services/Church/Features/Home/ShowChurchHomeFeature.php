<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Home;

use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;

/**
 * The satellite's address opens the person's area there (Roy 2026-11-06): an admin's is Admin (the families list),
 * everyone else's Serve (today's classes).
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Open the satellite home',
    persona: 'staff',
    happyPaths: ['story#42' => 'The satellite home opens an admin\'s Admin area and everyone else\'s Serve area'],
)]
#[AccessRow(AccessRowEnum::StaffArea)]
final class ShowChurchHomeFeature extends BaseChurchFeature
{
    public function handle(): RedirectResponse
    {
        return $this->scope()->canUseFullAdmin()
            ? redirect()->route('admin.families.index', $this->satellite())
            : redirect()->route('serve.home', $this->satellite());
    }
}
