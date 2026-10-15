<?php

declare(strict_types=1);

namespace App\Services\Org\Features\Duplicate;

use App\Data\Models\DuplicateCandidate;
use App\Domains\Family\DataObjects\DuplicatePairData;
use App\Domains\Family\Jobs\GetOpenDuplicatesJob;
use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use App\Services\Org\Features\BaseOrgFeature;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Open possible duplicates the viewer may review: those whose NEW family they may edit (plan §6). Each side
 * opens only for someone who may see that family.
 *
 * @extends BaseOrgFeature<Response>
 */
#[AccessRow(AccessRowEnum::ReviewDuplicates)]
final class ShowDuplicatesFeature extends BaseOrgFeature
{
    public function handle(): Response
    {
        $org = $this->organization();
        $scope = $this->scope();
        abort_unless($scope->canReviewDuplicates(), 404);

        $pairs = $this->run(new GetOpenDuplicatesJob(organizationId: $org->id))
            ->filter(fn (DuplicateCandidate $p): bool => $scope->canEditFamily(DuplicateCandidate::familyOf($p->subject)))
            ->map(fn (DuplicateCandidate $p): DuplicatePairData => DuplicatePairData::fromModel(
                $p,
                canOpenSubject: true,
                canOpenCandidate: $scope->canSeeFamily(DuplicateCandidate::familyOf($p->candidate)),
            ));

        return Inertia::render('Org/Duplicates/Index', [
            'church' => ['name' => $org->name, 'slug' => $org->slug],
            'duplicates' => array_values($pairs->all()),
        ]);
    }
}
