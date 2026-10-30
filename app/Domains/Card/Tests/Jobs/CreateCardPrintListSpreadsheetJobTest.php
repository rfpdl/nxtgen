<?php

declare(strict_types=1);

use App\Data\Models\CardRequest;
use App\Domains\Card\Jobs\CreateCardPrintListSpreadsheetJob;
use App\Enums\CardStepEnum;
use App\Enums\GenderEnum;
use App\Foundation\Support\NameKey;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;
use function Tests\Support\spreadsheetRows;

/*
 * The print list (name cards 04, Roy 2026-10-29): the approved cards as the sheet's Barcode tab has them — Full name,
 * Nickname, Level, Squad, Barcode — the Barcode the very code the door matches a scanned card against.
 */

it('writes the Barcode tab\'s header and a row per card: full name, nickname, Level, Squad, and the door\'s code', function (): void {
    $family = familyAt(satelliteOf(church()));
    $adam = childOf($family, ['first_name' => 'Adam', 'middle_name' => 'Matthias', 'last_name' => 'Abergos', 'nickname' => 'Adam', 'level' => 2, 'gender' => GenderEnum::Boy]);
    $lia = childOf($family, ['first_name' => 'Lía', 'middle_name' => null, 'last_name' => 'Abergos', 'nickname' => 'Lia', 'level' => 1, 'gender' => GenderEnum::Girl]);
    $requests = [
        CardRequest::factory()->for($adam)->create(['step' => CardStepEnum::Approved]),
        CardRequest::factory()->for($lia)->create(['step' => CardStepEnum::Approved]),
    ];

    $rows = spreadsheetRows((new CreateCardPrintListSpreadsheetJob(requests: $requests))->handle());

    expect($rows)->toBe([
        'Full name|Nickname|Level|Squad|Barcode',
        'Adam Matthias Abergos|Adam|Level 2|Level 2 Boys|ADAMMATTHIASABERGOS',
        'Lía Abergos|Lia|Level 1|Level 1|LIAABERGOS',
    ])->and(explode('|', $rows[1])[4])->toBe(NameKey::of('Adam', 'Matthias', 'Abergos'))
        ->and(explode('|', $rows[1])[4])->toBe($adam->fresh()?->name_key);
});

it('leaves Level and Squad empty for a child with no Level yet', function (): void {
    $child = childOf(familyAt(satelliteOf(church())), ['first_name' => 'Noa', 'middle_name' => null, 'last_name' => 'Cruz', 'nickname' => 'Noa', 'level' => null]);

    $rows = spreadsheetRows((new CreateCardPrintListSpreadsheetJob(requests: [CardRequest::factory()->for($child)->create(['step' => CardStepEnum::Approved])]))->handle());

    expect($rows[1])->toBe('Noa Cruz|Noa|||NOACRUZ');
});

it('writes a name that looks like a formula as plain text, never a formula', function (): void {
    $child = childOf(familyAt(satelliteOf(church())), ['first_name' => '=1+1', 'nickname' => '=HYPERLINK("x")']);

    $zip = new ZipArchive;
    $zip->open((new CreateCardPrintListSpreadsheetJob(requests: [CardRequest::factory()->for($child)->create(['step' => CardStepEnum::Approved])]))->handle());
    $sheet = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    expect($sheet)->not->toContain('<f>')->and($sheet)->toContain('=HYPERLINK');
});
