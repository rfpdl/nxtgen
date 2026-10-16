<?php

declare(strict_types=1);

namespace App\Domains\Registry\Values;

use App\Enums\ChildStatusEnum;
use App\Enums\GenderEnum;
use App\Enums\RelationshipEnum;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * One raw record of the Registry tab export (CSV), read by column position and parsed. The counterpart of
 * ApprovedChildRowData for the reviewed file. Nothing is guessed: a value that does not parse comes back null,
 * and the proposal quarantines or flags it.
 */
final readonly class RegistryCsvRecord
{
    /** @var list<string> */
    private array $cells;

    /**
     * A short or malformed record is padded, not dropped: its missing fields send it to quarantine, so the
     * reconciliation still accounts for it.
     *
     * @param  list<string>  $cells
     */
    public function __construct(array $cells)
    {
        $this->cells = array_pad(array_map(SheetCell::text(...), $cells), RegistryCsvColumn::width(), '');
    }

    public function get(RegistryCsvColumn $column): string
    {
        return $this->cells[$column->value] ?? '';
    }

    public function orNull(RegistryCsvColumn $column): ?string
    {
        return SheetCell::orNull($this->get($column));
    }

    public function yes(RegistryCsvColumn $column): bool
    {
        return SheetCell::yes($this->get($column));
    }

    public function dateOfBirth(): ?string
    {
        $year = $this->get(RegistryCsvColumn::BirthYear);
        $month = $this->get(RegistryCsvColumn::BirthMonth);
        $day = $this->get(RegistryCsvColumn::BirthDay);
        if (! ctype_digit($year) || ! ctype_digit($day)) {
            return null;
        }
        // Explicit allowlist: strtotime() turns a blank or unknown month into January.
        $months = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];
        $name = mb_strtolower($month);
        $index = array_search($name, $months, true);
        $shortIndex = array_search($name, array_map(fn (string $m): string => Str::substr($m, 0, 3), $months), true);
        $m = match (true) {
            ctype_digit($month) => (int) $month,
            $index !== false => $index + 1,
            $shortIndex !== false => $shortIndex + 1,
            default => 0,
        };
        if ($m < 1 || $m > 12 || ! checkdate($m, (int) $day, (int) $year)) {
            return null;
        }

        return CarbonImmutable::create((int) $year, $m, (int) $day)?->toDateString();
    }

    public function gender(): ?GenderEnum
    {
        return match (mb_strtolower($this->get(RegistryCsvColumn::Gender))) {
            'male', 'boy', 'm' => GenderEnum::Boy,
            'female', 'girl', 'f' => GenderEnum::Girl,
            default => null,
        };
    }

    public function relationship(): RelationshipEnum
    {
        return match (mb_strtolower($this->get(RegistryCsvColumn::Relationship))) {
            'mother', 'mom', 'mum' => RelationshipEnum::Mother,
            'father', 'dad' => RelationshipEnum::Father,
            'grandmother', 'grandfather', 'grandparent' => RelationshipEnum::Grandparent,
            'aunt', 'uncle' => RelationshipEnum::AuntUncle,
            'brother', 'sister', 'sibling' => RelationshipEnum::Sibling,
            'parents', 'guardian' => RelationshipEnum::Guardian,
            default => RelationshipEnum::Other,
        };
    }

    public function guardianName(): GuardianName
    {
        return GuardianName::parse($this->get(RegistryCsvColumn::Guardian));
    }

    public function status(): ChildStatusEnum
    {
        $status = mb_strtoupper($this->get(RegistryCsvColumn::Status));

        return match (true) {
            Str::contains($status, 'ELEVATE') => ChildStatusEnum::Graduated,
            Str::contains($status, 'WENT HOME'), Str::contains($status, 'REMOVE') => ChildStatusEnum::Inactive,
            default => ChildStatusEnum::Active,
        };
    }

    public function isDuplicate(): bool
    {
        return mb_strtoupper($this->get(RegistryCsvColumn::Status)) === 'DUPLICATE';
    }
}
