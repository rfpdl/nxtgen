<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Child;

use App\Data\Models\Child;
use App\Data\Models\Family;
use App\Domains\Church\Jobs\GetChurchForUpdateJob;
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
 * A church admin corrects a child's Level outside Moving-Up (plan §3.2; DECISIONS 2026-10-17 — it replaced
 * "correct a child's group"). The Input guarantees a Level of the church-wide chart; there is no gender rule any
 * more, because the child's Squad at a Satellite follows from Level + gender. Noted on the child's history.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Correct a child\'s Level',
    persona: 'church admin',
    happyPaths: ['story#64' => 'A church admin corrects a Child\'s Level; who, when and the old Level are noted'],
    unhappyPaths: ['story#65' => 'Only church admins, only Levels on the chart, only this church'],
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
            $this->run(new UpdateChildLevelJob(child: $child, level: $input->level, by: $this->staff()));
        });

        return back()->with('success', "{$child->displayName()} is now in ".LevelChart::name($input->level).'.');
    }
}
