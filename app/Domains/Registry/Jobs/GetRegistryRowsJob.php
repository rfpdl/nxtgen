<?php

declare(strict_types=1);

namespace App\Domains\Registry\Jobs;

use App\Domains\Registry\DataObjects\RegistryRowData;
use App\Enums\ChildStatusEnum;
use App\Enums\GenderEnum;
use App\Enums\RelationshipEnum;
use App\Foundation\Jobs\BaseJob;
use Carbon\CarbonImmutable;
use SplFileObject;

/**
 * Read the Registry tab export (CSV). Columns are read by POSITION because the sheet has two columns named
 * "Complete Name". fgetcsv handles quoted multi-line answers. Nothing is guessed: a value that does not
 * parse becomes null and the proposal quarantines or flags it.
 *
 * @extends BaseJob<list<RegistryRowData>>
 */
final class GetRegistryRowsJob extends BaseJob
{
    private const int FIRST = 0;

    private const int LAST = 1;

    private const int NICK = 2;

    private const int GENDER = 3;

    private const int YEAR = 4;

    private const int MONTH = 5;

    private const int DAY = 6;

    private const int ALLERGY_LIST = 8;

    private const int DISLIKES = 9;

    private const int FEARS = 10;

    private const int NOTES = 11;

    private const int FB_GROUP = 12;

    private const int FB_ACCOUNT = 13;

    private const int GUARDIAN = 14;

    private const int RELATION = 15;

    private const int PHONE = 16;

    private const int EMAIL = 17;

    private const int AREA = 18;

    private const int IN_DGROUP = 19;

    private const int DGROUP_AREA = 20;

    private const int DGROUP_LEADER = 21;

    private const int MINISTRY = 22;

    private const int JOIN_DGROUP = 23;

    private const int LIFESTAGE = 24;

    private const int PRIVACY = 25;

    private const int DGROUP_LEADER_PHONE = 26;

    private const int SCHEDULE = 27;

    private const int STATUS = 33;

    public function __construct(private readonly string $path) {}

    /** @return list<RegistryRowData> */
    public function handle(): array
    {
        $file = new SplFileObject($this->path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD);

        $rows = [];
        $line = 0;
        foreach ($file as $cells) {
            $line++;
            if ($line === 1 || ! is_array($cells)) {
                continue;
            }
            $c = array_values(array_map(fn (mixed $v): string => is_scalar($v) ? trim((string) $v) : '', $cells));
            if (implode('', $c) === '') {
                continue; // a genuinely blank line
            }
            // A short or malformed record is padded, not dropped: its missing fields send it to quarantine,
            // so the reconciliation still accounts for it (review-spar B2).
            $c = array_pad($c, self::STATUS + 1, '');
            $rows[] = $this->row($line, $c);
        }

        return $rows;
    }

    /** @param list<string> $c */
    private function row(int $line, array $c): RegistryRowData
    {
        $email = mb_strtolower($c[self::EMAIL] ?? '');
        $first = $c[self::FIRST] ?? '';
        $last = $c[self::LAST] ?? '';
        $dob = $this->date($c[self::YEAR] ?? '', $c[self::MONTH] ?? '', $c[self::DAY] ?? '');
        [$guardianFirst, $guardianLast] = $this->splitName($c[self::GUARDIAN] ?? '');
        $status = mb_strtoupper($c[self::STATUS] ?? '');

        return new RegistryRowData(
            rowNumber: $line,
            sourceKey: substr(hash('sha256', $email.'|'.mb_strtolower($first).'|'.$dob), 0, 20),
            childFirstName: $first,
            childLastName: $last,
            nickname: ($c[self::NICK] ?? '') !== '' ? $c[self::NICK] : $first,
            gender: $this->gender($c[self::GENDER] ?? ''),
            dateOfBirth: $dob,
            allergies: self::blank($c[self::ALLERGY_LIST] ?? ''),
            dislikes: self::blank($c[self::DISLIKES] ?? ''),
            fears: self::blank($c[self::FEARS] ?? ''),
            parentNotes: self::blank($c[self::NOTES] ?? ''),
            wantsFbGroup: self::yes($c[self::FB_GROUP] ?? ''),
            fbAccount: self::blank($c[self::FB_ACCOUNT] ?? ''),
            guardianFirstName: $guardianFirst,
            guardianLastName: $guardianLast,
            relationship: $this->relationship($c[self::RELATION] ?? ''),
            phone: self::blank($c[self::PHONE] ?? ''),
            phoneKey: substr((string) preg_replace('/\D+/', '', $c[self::PHONE] ?? ''), -9),
            email: filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? $email : '',
            area: self::blank($c[self::AREA] ?? ''),
            inDgroup: self::yes($c[self::IN_DGROUP] ?? ''),
            dgroupArea: self::blank($c[self::DGROUP_AREA] ?? ''),
            dgroupLeaderName: self::blank($c[self::DGROUP_LEADER] ?? ''),
            dgroupLeaderPhone: self::blank($c[self::DGROUP_LEADER_PHONE] ?? ''),
            ministry: self::blank($c[self::MINISTRY] ?? ''),
            wantsDgroup: self::yes($c[self::JOIN_DGROUP] ?? ''),
            lifestage: self::blank($c[self::LIFESTAGE] ?? ''),
            privacyConsented: self::yes($c[self::PRIVACY] ?? ''),
            schedule: $c[self::SCHEDULE] ?? '',
            status: match (true) {
                str_contains($status, 'ELEVATE') => ChildStatusEnum::Graduated,
                str_contains($status, 'WENT HOME'), str_contains($status, 'REMOVE') => ChildStatusEnum::Inactive,
                default => ChildStatusEnum::Active,
            },
            isDuplicate: $status === 'DUPLICATE',
        );
    }

    private function date(string $year, string $month, string $day): ?string
    {
        if (! ctype_digit($year) || ! ctype_digit($day)) {
            return null;
        }
        // Explicit allowlist: strtotime() turns a blank or unknown month into January (Codex diff review).
        $months = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];
        $name = mb_strtolower(trim($month));
        $index = array_search($name, $months, true);
        $shortIndex = array_search($name, array_map(fn (string $m): string => substr($m, 0, 3), $months), true);
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

    private function gender(string $value): ?GenderEnum
    {
        return match (mb_strtolower($value)) {
            'male', 'boy', 'm' => GenderEnum::Boy,
            'female', 'girl', 'f' => GenderEnum::Girl,
            default => null,
        };
    }

    private function relationship(string $value): RelationshipEnum
    {
        return match (mb_strtolower($value)) {
            'mother', 'mom', 'mum' => RelationshipEnum::Mother,
            'father', 'dad' => RelationshipEnum::Father,
            'grandmother', 'grandfather', 'grandparent' => RelationshipEnum::Grandparent,
            'aunt', 'uncle' => RelationshipEnum::AuntUncle,
            'brother', 'sister', 'sibling' => RelationshipEnum::Sibling,
            'parents', 'guardian' => RelationshipEnum::Guardian,
            default => RelationshipEnum::Other,
        };
    }

    /** @return array{0: string, 1: string} */
    private function splitName(string $full): array
    {
        $parts = preg_split('/\s+/', trim($full)) ?: [];
        if (count($parts) < 2) {
            return [trim($full), ''];
        }
        $last = (string) array_pop($parts);

        return [implode(' ', $parts), $last];
    }

    private static function blank(string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    private static function yes(string $value): bool
    {
        return str_starts_with(mb_strtolower($value), 'y');
    }
}
