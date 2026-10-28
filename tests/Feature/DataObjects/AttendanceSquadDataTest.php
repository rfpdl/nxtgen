<?php

declare(strict_types=1);

use App\Data\Models\CheckIn;
use App\Data\Models\Child;
use App\Data\Models\Squad;
use App\Data\Models\SquadHeadCount;
use App\Domains\CheckIn\DataObjects\AttendanceSquadData;
use App\Enums\GenderEnum;
use Carbon\CarbonImmutable;

/* Attendance's Squads: the children in, by Squad — and a Squad that was counted but has no one in now, in its place. */

function squadOf(int $id, int $level, ?GenderEnum $gender): Squad
{
    return (new Squad)->forceFill(['id' => $id, 'level' => $level, 'gender' => $gender]);
}

function inSquad(int $id, Squad $squad, string $nickname): CheckIn
{
    return (new CheckIn)->forceFill(['id' => $id, 'squad_id' => $squad->id, 'checked_in_at' => CarbonImmutable::parse('2026-10-31 09:00:00'), 'present_at' => null])
        ->setRelation('squad', $squad)
        ->setRelation('child', (new Child)->forceFill(['nickname' => $nickname, 'first_name' => $nickname, 'middle_name' => null, 'last_name' => 'Cruz']))
        ->setRelation('checkedInBy', null);
}

function countOf(Squad $squad, int $count): SquadHeadCount
{
    return (new SquadHeadCount)->forceFill(['squad_id' => $squad->id, 'head_count' => $count, 'counted_at' => CarbonImmutable::parse('2026-10-31 09:30:00')])
        ->setRelation('squad', $squad)
        ->setRelation('countedBy', null);
}

it('adds a counted Squad with no one in now as "0 in", in the Attendance order among the others', function (): void {
    $one = squadOf(1, 1, null);
    $boys3 = squadOf(5, 3, GenderEnum::Boy);
    $girls2 = squadOf(4, 2, GenderEnum::Girl);
    $boys2 = squadOf(3, 2, GenderEnum::Boy);

    $squads = AttendanceSquadData::groupsOf(
        [inSquad(10, $one, 'Ben'), inSquad(11, $girls2, 'Amara')],
        [5 => countOf($boys3, 2), 3 => countOf($boys2, 1), 4 => countOf($girls2, 1)],
        'Asia/Dubai',
    );

    expect(array_map(fn (AttendanceSquadData $s): array => [$s->name, $s->count, $s->head_count?->count], $squads))->toBe([
        ['Level 1', 1, null],
        ['Level 2 Boys', 0, 1],
        ['Level 2 Girls', 1, 1],
        ['Level 3 Boys', 0, 2],
    ])->and($squads[1]->children)->toBe([]);
});
