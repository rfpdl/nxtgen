<?php

declare(strict_types=1);

namespace App\Services\Platform\Features\People;

use App\Domains\Church\Jobs\GetRoleAssignmentJob;
use App\Foundation\Attributes\UserStory;
use App\Operations\Church\RemoveRoleOperation;
use App\Services\Platform\Features\BasePlatformFeature;
use Illuminate\Http\RedirectResponse;

/**
 * Switch any Role off, kept for the record (spec item 12) — never a satellite's last admin (RemoveRoleOperation).
 *
 * @extends BasePlatformFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Remove a Role anywhere',
    persona: 'platform admin',
    happyPaths: ['story#197' => 'The platform admin switches any Role off — never a Satellite\'s last admin'],
)]
final class RemoveRoleFeature extends BasePlatformFeature
{
    public function __construct(private readonly int $roleId) {}

    public function handle(): RedirectResponse
    {
        $church = $this->church();
        $assignment = $this->run(new GetRoleAssignmentJob(church: $church, id: $this->roleId));
        abort_unless($assignment->is_active, 404);

        $refused = $this->run(new RemoveRoleOperation(church: $church, assignment: $assignment));

        return $refused === null ? back()->with('success', 'Role removed.') : back()->with('error', $refused);
    }
}
