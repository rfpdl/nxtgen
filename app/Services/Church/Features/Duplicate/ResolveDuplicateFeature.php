<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Duplicate;

use App\Data\Models\DuplicateCandidate;
use App\Domains\Family\Jobs\GetDuplicateCandidateJob;
use App\Domains\Family\Jobs\UpdateDuplicateCandidateStatusJob;
use App\Enums\AccessRowEnum;
use App\Enums\DuplicateStatusEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Data\ResolveDuplicateInput;
use App\Services\Church\Features\BaseChurchFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Answer a possible duplicate: not the same family, or the same (flagged for the later merge — nothing is
 * merged here). Only an OPEN pair, only by someone who may edit the new family. Noted on the new family.
 *
 * @extends BaseChurchFeature<RedirectResponse>
 */
#[UserStory(
    title: 'Answer a possible duplicate',
    persona: 'church admin or satellite admin',
    happyPaths: ['story#72' => 'An Admin dismisses or confirms a pair; it is noted, and nothing is merged'],
    unhappyPaths: ['story#73' => 'Refused outside their scope, once answered, or for any other answer'],
)]
#[AccessRow(AccessRowEnum::ReviewDuplicates)]
final class ResolveDuplicateFeature extends BaseChurchFeature
{
    public function __construct(private readonly int $pairId) {}

    public function handle(ResolveDuplicateInput $input): RedirectResponse
    {
        $scope = $this->scope();
        abort_unless($scope->canReviewDuplicates(), 404);
        $pair = $this->run(new GetDuplicateCandidateJob(church: $this->church(), id: $this->pairId));
        abort_unless($pair->status === DuplicateStatusEnum::Open && $scope->canEditFamily(DuplicateCandidate::familyOf($pair->subject)), 404);

        // The answer and its note on the new family together, or neither.
        DB::transaction(fn () => $this->run(new UpdateDuplicateCandidateStatusJob(pair: $pair, status: $input->status, by: $this->staff())));

        return back()->with('success', "Saved: {$input->status->answer()}.");
    }
}
