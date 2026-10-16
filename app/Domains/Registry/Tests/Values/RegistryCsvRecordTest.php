<?php

declare(strict_types=1);

use App\Domains\Registry\Values\GuardianName;
use App\Domains\Registry\Values\RegistryCsvColumn as Col;
use App\Domains\Registry\Values\RegistryCsvRecord;
use App\Enums\ChildStatusEnum;
use App\Enums\GenderEnum;
use App\Enums\RelationshipEnum;

/* One raw registry record, read by column position; a value that does not parse is null, never guessed. */

/** @param array<int, string> $cells column position => value */
function csvRow(array $cells): RegistryCsvRecord
{
    return new RegistryCsvRecord(array_values(array_replace(array_fill(0, Col::width(), ''), $cells)));
}

it('reads a birthday from a month name, a short name or a number', function (string $month): void {
    expect(csvRow([Col::BirthYear->value => '2019', Col::BirthMonth->value => $month, Col::BirthDay->value => '10'])->dateOfBirth())
        ->toBe('2019-05-10');
})->with(['May', 'may', '5']);

it('reads no birthday from a blank, unknown or impossible date', function (string $year, string $month, string $day): void {
    expect(csvRow([Col::BirthYear->value => $year, Col::BirthMonth->value => $month, Col::BirthDay->value => $day])->dateOfBirth())
        ->toBeNull();
})->with([['2019', '', '10'], ['2019', 'Maytember', '10'], ['2019', 'February', '30'], ['', 'May', '10'], ['2019', '13', '1']]);

it('reads boy or girl, and nothing it does not recognise', function (): void {
    expect(csvRow([Col::Gender->value => 'Male'])->gender())->toBe(GenderEnum::Boy)
        ->and(csvRow([Col::Gender->value => 'F'])->gender())->toBe(GenderEnum::Girl)
        ->and(csvRow([Col::Gender->value => 'unsure'])->gender())->toBeNull();
});

it('reads the relationship, falling back to Other', function (): void {
    expect(csvRow([Col::Relationship->value => 'Mum'])->relationship())->toBe(RelationshipEnum::Mother)
        ->and(csvRow([Col::Relationship->value => 'Uncle'])->relationship())->toBe(RelationshipEnum::AuntUncle)
        ->and(csvRow([Col::Relationship->value => 'Neighbour'])->relationship())->toBe(RelationshipEnum::Other);
});

it('splits the guardian\'s name at the last word', function (): void {
    expect(csvRow([Col::Guardian->value => 'Mary Jane  Cruz'])->guardianName())->toEqual(new GuardianName('Mary Jane', 'Cruz'))
        ->and(csvRow([Col::Guardian->value => 'Cher'])->guardianName())->toEqual(new GuardianName('Cher', ''));
});

it('reads the status column', function (string $status, ChildStatusEnum $expected, bool $duplicate): void {
    $row = csvRow([Col::Status->value => $status]);

    expect($row->status())->toBe($expected)->and($row->isDuplicate())->toBe($duplicate);
})->with([
    ['UNIQUE', ChildStatusEnum::Active, false],
    ['elevated', ChildStatusEnum::Graduated, false],
    ['Went home', ChildStatusEnum::Inactive, false],
    ['REMOVED', ChildStatusEnum::Inactive, false],
    ['duplicate', ChildStatusEnum::Active, true],
]);

it('reads a yes/no answer from its first letter, and a blank as null', function (): void {
    expect(csvRow([Col::Privacy->value => 'Yes, I agree'])->yes(Col::Privacy))->toBeTrue()
        ->and(csvRow([Col::Privacy->value => 'No'])->yes(Col::Privacy))->toBeFalse()
        ->and(csvRow([])->orNull(Col::Area))->toBeNull()
        ->and(csvRow([Col::Area->value => 'Barsha'])->orNull(Col::Area))->toBe('Barsha');
});

it('trims every cell and pads a short record to the last column it reads', function (): void {
    $record = new RegistryCsvRecord(['  Amara ', 'Cruz']);

    expect($record->get(Col::FirstName))->toBe('Amara')
        ->and($record->get(Col::Status))->toBe('')
        ->and($record->status())->toBe(ChildStatusEnum::Active);
});
