<?php

declare(strict_types=1);

namespace App\Services\Church\Features\Duplicate;

use App\Data\Models\DuplicateCandidate;
use App\Domains\Family\DataObjects\DuplicatePairData;
use App\Domains\Family\Jobs\GetOpenDuplicatesJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Foundation\Attributes\UserStory;
use App\Services\Church\Features\BaseChurchFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Open possible duplicates the viewer may review: those whose NEW family they may edit (plan §6). Each side
 * opens only for someone who may see that family.
 *
 * @extends BaseChurchFeature<Response>
 */
#[UserStory(
    title: 'See possible duplicates',
    persona: 'church admin or satellite admin',
    happyPaths: ['story#70' => 'An Admin sees possible duplicates side by side, emails masked, within their scope'],
    unhappyPaths: ['story#71' => 'Not there for a Lifeshaper'],
)]
#[AccessRow(AccessRowEnum::ReviewDuplicates)]
final class ShowDuplicatesFeature extends BaseChurchFeature
{
    public function handle(): Response
    {
        $church = $this->church();
        $scope = $this->scope();
        abort_unless($scope->canReviewDuplicates(), 404);

        $pairs = $this->run(new GetOpenDuplicatesJob(church: $church))
            ->filter(fn (DuplicateCandidate $p): bool => $scope->canEditFamily(DuplicateCandidate::familyOf($p->subject)))
            ->map(fn (DuplicateCandidate $p): DuplicatePairData => DuplicatePairData::fromModel(
                $p,
                canOpenSubject: true,
                canOpenCandidate: $scope->canSeeFamily(DuplicateCandidate::familyOf($p->candidate)),
            ));

        return Inertia::render('Church/Duplicates/Index', [
            'duplicates' => array_values($pairs->all()),
        ]);
    }
}
