<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Child;

use App\Domains\Family\Jobs\CreateSystemNoteJob;
use App\Domains\Family\Jobs\GetChurchChildJob;
use App\Domains\Family\Jobs\UpdateChildLevelJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
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
#[AccessRow(AccessRowEnum::SetChildLevel)]
final class UpdateChildLevelFeature extends BaseChurchFeature
{
    public function __construct(private readonly int $childId) {}

    public function handle(UpdateChildLevelInput $input): RedirectResponse
    {
        abort_unless($this->scope()->canSetLevel(), 404);
        $child = $this->run(new GetChurchChildJob(churchId: $this->church()->id, childId: $this->childId));

        $from = $child->levelName() ?? 'no Level';
        $to = LevelChart::name($input->level);
        DB::transaction(function () use ($child, $input, $from, $to): void {
            $this->run(new UpdateChildLevelJob(child: $child, level: $input->level, by: $this->staff()));
            $this->run(new CreateSystemNoteJob(about: $child, body: "{$child->displayName()}: Level changed from {$from} to {$to}.", by: $this->staff()));
        });

        return back()->with('success', "{$child->displayName()} is now in {$to}.");
    }
}
