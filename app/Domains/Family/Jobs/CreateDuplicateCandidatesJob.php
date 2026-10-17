<?php

declare(strict_types=1);

namespace App\Domains\Family\Jobs;

use App\Data\Models\Child;
use App\Data\Models\DuplicateCandidate;
use App\Data\Models\Family;
use App\Data\Models\Guardian;
use App\Enums\DuplicateReasonEnum;
use App\Enums\DuplicateStatusEnum;
use App\Foundation\Jobs\BaseJob;

/**
 * Record possible duplicates of a just-registered or just-changed family (a child or a guardian added) for staff
 * review (plan §4.2). Candidates only — a shared phone or a namesake is evidence, not identity. A pair is recorded
 * once, whichever of its families it was found from, with the newer record as the one to answer for.
 *
 *   family ↔ family  when a guardian shares a phone (last 9 digits)
 *   child  ↔ child   when first name + last name + birthday match
 *
 * @extends BaseJob<int>
 */
final class CreateDuplicateCandidatesJob extends BaseJob
{
    public function __construct(private readonly Family $family) {}

    /** @return int number of candidates recorded */
    public function handle(): int
    {
        $created = 0;

        $phones = $this->family->guardians()->get()
            ->map(fn (Guardian $g): string => self::phoneKey((string) $g->phone))
            ->filter(fn (string $k): bool => strlen($k) >= 7)
            ->unique();

        if ($phones->isNotEmpty()) {
            $otherFamilies = Guardian::query()
                ->where('church_id', $this->family->church_id)
                ->where('family_id', '!=', $this->family->id)
                ->whereNotNull('phone')
                ->get(['family_id', 'phone'])
                ->filter(fn (Guardian $g): bool => $phones->contains(self::phoneKey((string) $g->phone)))
                ->map(fn (Guardian $g): int => $g->family_id)
                ->unique();

            foreach ($otherFamilies as $familyId) {
                $created += $this->record('family', $this->family->id, $familyId, DuplicateReasonEnum::SamePhone);
            }
        }

        foreach ($this->family->children()->get() as $child) {
            $twins = Child::query()
                ->where('church_id', $this->family->church_id)
                ->where('family_id', '!=', $this->family->id)
                ->whereRaw('LOWER(first_name) = ?', [mb_strtolower($child->first_name)])
                ->whereRaw('LOWER(last_name) = ?', [mb_strtolower($child->last_name)])
                ->whereDate('date_of_birth', $child->date_of_birth->toDateString())
                ->get(['id']);

            foreach ($twins as $twin) {
                $created += $this->record('child', $child->id, $twin->id, DuplicateReasonEnum::SameNameDob);
            }
        }

        return $created;
    }

    /**
     * One pair, the NEWER record (the higher id: the family registered later, or the child added later) as its
     * subject — whichever side it was found from — so the newer family's editor answers it (plan §6). Recorded once.
     */
    private function record(string $type, int $foundFrom, int $other, DuplicateReasonEnum $reason): int
    {
        $model = DuplicateCandidate::query()->firstOrCreate(
            ['subject_type' => $type, 'subject_id' => max($foundFrom, $other), 'candidate_id' => min($foundFrom, $other)],
            ['church_id' => $this->family->church_id, 'reason' => $reason, 'status' => DuplicateStatusEnum::default()],
        );

        return $model->wasRecentlyCreated ? 1 : 0;
    }

    private static function phoneKey(string $phone): string
    {
        return substr((string) preg_replace('/\D+/', '', $phone), -9);
    }
}
