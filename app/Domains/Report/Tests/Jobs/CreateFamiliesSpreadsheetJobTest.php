<?php

declare(strict_types=1);

use App\Data\Models\Child;
use App\Data\Models\Family;
use App\Domains\Report\Jobs\CreateFamiliesSpreadsheetJob;

it('writes a nickname that looks like a formula as plain text, never a formula (Codex diff review, high)', function (): void {
    $family = Family::factory()->create();
    Child::factory()->create(['family_id' => $family->id, 'church_id' => $family->church_id, 'nickname' => '=HYPERLINK("http://evil","x")', 'allergies' => '=1+1']);

    $families = Family::query()->with(['primaryGuardian.user', 'homeSatellite', 'children.group'])->get();
    $path = (new CreateFamiliesSpreadsheetJob(families: $families, fullEmail: true))->handle();

    $zip = new ZipArchive;
    $zip->open($path);
    $sheet = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    expect($sheet)->not->toContain('<f>')
        ->and($sheet)->toContain('=HYPERLINK');
});
