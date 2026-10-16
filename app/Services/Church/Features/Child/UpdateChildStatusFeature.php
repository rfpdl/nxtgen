<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Child;

use App\Domains\Family\Jobs\UpdateChildStatusJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Data\UpdateChildStatusInput;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Active / inactive / graduated — church admin, or admin of the family's home satellite (plan §3.2).
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Set a child\'s status',
    persona: 'church admin or satellite admin',
    happyPaths: ['story#62' => 'An Admin sets a Child\'s status (e.g. Graduated) and it is noted'],
    unhappyPaths: ['story#63' => 'A Lifeshaper or another Satellite\'s Admin cannot'],
)]
#[AccessRow(AccessRowEnum::SetChildStatus)]
final class UpdateChildStatusFeature extends BaseChurchFeature
{
    public function __construct(private readonly int $childId) {}

    public function handle(UpdateChildStatusInput $input): RedirectResponse
    {
        $child = $this->scopedChild($this->childId, $this->scope()->canEditFamily(...));

        // The status and its history note together, or neither.
        DB::transaction(fn () => $this->run(new UpdateChildStatusJob(child: $child, status: $input->status, by: $this->staff())));

        return back()->with('success', "{$child->displayName()} is now {$input->status->label()}.");
    }
}
