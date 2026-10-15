<?php

declare(strict_types=1);

namespace App\Services\Org\Features\Duplicate;

use App\Data\Models\DuplicateCandidate;
use App\Domains\Family\Jobs\CreateSystemNoteJob;
use App\Domains\Family\Jobs\GetDuplicateCandidateJob;
use App\Domains\Family\Jobs\UpdateDuplicateCandidateStatusJob;
use App\Enums\AccessRowEnum;
use App\Enums\DuplicateStatusEnum;
use App\Foundation\Attributes\AccessRow;
use App\Services\Org\Data\ResolveDuplicateInput;
use App\Services\Org\Features\BaseOrgFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Answer a possible duplicate: not the same family, or the same (flagged for the later merge — nothing is
 * merged here). Only an OPEN pair, only by someone who may edit the new family. Noted on the new family.
 *
 * @extends BaseOrgFeature<RedirectResponse>
 */
#[AccessRow(AccessRowEnum::ReviewDuplicates)]
final class ResolveDuplicateFeature extends BaseOrgFeature
{
    public function __construct(private readonly int $pairId) {}

    public function handle(ResolveDuplicateInput $input): RedirectResponse
    {
        $scope = $this->scope();
        abort_unless($scope->canReviewDuplicates(), 404);
        $pair = $this->run(new GetDuplicateCandidateJob(organizationId: $this->organization()->id, id: $this->pairId));
        $newFamily = DuplicateCandidate::familyOf($pair->subject);
        abort_unless($pair->status === DuplicateStatusEnum::Open && $scope->canEditFamily($newFamily), 404);

        $other = DuplicateCandidate::familyOf($pair->candidate)->contactName();
        $answer = $input->status === DuplicateStatusEnum::Confirmed ? 'the same family, flagged for merging' : 'not the same family';

        DB::transaction(function () use ($pair, $input, $newFamily, $other, $answer): void {
            $this->run(new UpdateDuplicateCandidateStatusJob(pair: $pair, status: $input->status, by: $this->staff()));
            $this->run(new CreateSystemNoteJob(
                about: $newFamily,
                body: "Possible duplicate of {$other} (".mb_strtolower($pair->reason->label())."): {$answer}.",
                by: $this->staff(),
            ));
        });

        return back()->with('success', "Saved: {$answer}.");
    }
}
