<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Child;

use App\Data\Models\Child;
use App\Data\Models\Family;
use App\Domains\Church\Jobs\GetChurchForUpdateJob;
use App\Domains\Family\Jobs\GetChildForUpdateJob;
use App\Domains\Family\Jobs\GetFamilyForUpdateJob;
use App\Domains\Family\Jobs\UpdateChildLevelJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Foundation\Support\LevelChart;
use App\Services\Church\Data\UpdateChildLevelInput;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * A satellite admin corrects a child's Level outside Moving-Up (plan §3.2; DECISIONS 2026-10-17, 2026-11-08 — it replaced
 * "correct a child's group"). The Input guarantees a Level of the church-wide chart; there is no gender rule any
 * more, because the child's Squad at a Satellite follows from Level + gender. Noted on the child's history.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Correct a child\'s Level',
    persona: 'satellite admin',
    happyPaths: ['story#64' => 'An admin of the Child\'s home Satellite corrects their Level; who, when and the old Level are noted'],
    unhappyPaths: ['story#65' => 'Only that Satellite\'s admins, only Levels on the chart, only this church'],
)]
#[AccessRow(AccessRowEnum::SetChildLevel)]
final class UpdateChildLevelFeature extends BaseChurchFeature
{
    public function __construct(private readonly Child $child) {}

    public function handle(UpdateChildLevelInput $input): RedirectResponse
    {
        // A child of a family at this address's satellite (spec item 10): elsewhere, at its own satellite's address.
        $child = $this->scopedChild($this->child, fn (Family $family): bool => $this->scope()->canSetLevel() && $this->scope()->canEditFamily($family));

        // The Level and its history note together, or neither.
        // After the church's row, as Moving-Up takes it: the two queue instead of overwriting each other (Astra, Moving-Up).
        DB::transaction(function () use ($child, $input): void {
            $this->run(new GetChurchForUpdateJob(church: $this->church()));
            // Still this satellite's family, its row locked till the end: one that moved meanwhile is not (Astra).
            abort_unless($this->scope()->canEditFamily($this->run(new GetFamilyForUpdateJob(family: $child->family))), 404);
            // The child as they are now — a Moving-Up may have committed since the page loaded them (issue 06 review).
            $this->run(new UpdateChildLevelJob(child: $this->run(new GetChildForUpdateJob(child: $child)), level: $input->level, by: $this->staff()));
        });

        return back()->with('success', "{$child->displayName()} is now in ".LevelChart::name($input->level).'.');
    }
}
