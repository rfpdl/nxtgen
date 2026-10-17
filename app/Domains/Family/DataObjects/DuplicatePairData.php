<?php

declare(strict_types=1);

namespace App\Domains\Family\DataObjects;

use App\Data\Models\DuplicateCandidate;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One possible duplicate on a family's page: the new family and the existing one, side by side, in the
 * church-wide search's limited view (FamilySearchResultData: masked email, no phone, no health).
 */
#[TypeScript]
final class DuplicatePairData extends Data
{
    public function __construct(
        public int $id,
        public string $reason_label,
        public FamilySearchResultData $subject,
        public FamilySearchResultData $candidate,
    ) {}

    public static function fromModel(DuplicateCandidate $pair, bool $canOpenSubject, bool $canOpenCandidate): self
    {
        return new self(
            id: $pair->id,
            reason_label: $pair->reason->label(),
            subject: FamilySearchResultData::fromModel(DuplicateCandidate::familyOf($pair->subject), $canOpenSubject, mayResend: false),
            candidate: FamilySearchResultData::fromModel(DuplicateCandidate::familyOf($pair->candidate), $canOpenCandidate, mayResend: false),
        );
    }
}
