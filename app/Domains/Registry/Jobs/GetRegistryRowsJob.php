<?php

declare(strict_types=1);

namespace App\Domains\Registry\Jobs;

use App\Domains\Registry\DataObjects\RegistryRowData;
use App\Domains\Registry\Values\RegistryCsvColumn as Col;
use App\Domains\Registry\Values\RegistryCsvRecord;
use App\Foundation\Jobs\BaseJob;
use Illuminate\Support\Str;
use SplFileObject;

/**
 * Read the Registry tab export (CSV). fgetcsv handles quoted multi-line answers; RegistryCsvRecord reads each
 * record by column position and parses its values. Nothing is guessed: a value that does not parse becomes
 * null and the proposal quarantines or flags it.
 *
 * @extends BaseJob<list<RegistryRowData>>
 */
final class GetRegistryRowsJob extends BaseJob
{
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
            // A short or malformed record is padded, not dropped (RegistryCsvRecord), so it reaches quarantine.
            $rows[] = $this->row($line, new RegistryCsvRecord($c));
        }

        return $rows;
    }

    private function row(int $line, RegistryCsvRecord $c): RegistryRowData
    {
        $email = mb_strtolower($c->get(Col::Email));
        $first = $c->get(Col::FirstName);
        $dob = $c->dateOfBirth();
        $guardian = $c->guardianName();

        return new RegistryRowData(
            row_number: $line,
            source_key: Str::substr(hash('sha256', $email.'|'.mb_strtolower($first).'|'.$dob), 0, 20),
            child_first_name: $first,
            child_last_name: $c->get(Col::LastName),
            nickname: $c->get(Col::Nickname) !== '' ? $c->get(Col::Nickname) : $first,
            gender: $c->gender(),
            date_of_birth: $dob,
            allergies: $c->orNull(Col::Allergies),
            dislikes: $c->orNull(Col::Dislikes),
            fears: $c->orNull(Col::Fears),
            guardian_notes: $c->orNull(Col::Notes),
            wants_fb_group: $c->yes(Col::WantsFbGroup),
            fb_account: $c->orNull(Col::FbAccount),
            guardian_first_name: $guardian->first,
            guardian_last_name: $guardian->last,
            relationship: $c->relationship(),
            phone: $c->orNull(Col::Phone),
            phone_key: Str::substr((string) preg_replace('/\D+/', '', $c->get(Col::Phone)), -9),
            email: filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? $email : '',
            area: $c->orNull(Col::Area),
            in_dgroup: $c->yes(Col::InDgroup),
            dgroup_area: $c->orNull(Col::DgroupArea),
            dgroup_leader_name: $c->orNull(Col::DgroupLeader),
            dgroup_leader_phone: $c->orNull(Col::DgroupLeaderPhone),
            ministry: $c->orNull(Col::Ministry),
            wants_dgroup: $c->yes(Col::WantsDgroup),
            lifestage: $c->orNull(Col::Lifestage),
            privacy_consented: $c->yes(Col::Privacy),
            schedule: $c->get(Col::Schedule),
            status: $c->status(),
            is_duplicate: $c->isDuplicate(),
        );
    }
}
