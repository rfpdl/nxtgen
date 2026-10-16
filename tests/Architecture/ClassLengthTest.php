<?php

declare(strict_types=1);

namespace Tests\Architecture;

/**
 * Every unit under `app/` has a code-line budget set by its LAYER — ported from aegis's `ClassLengthArchTest`, with
 * aegis's budgets unchanged so a unit that is too big there is too big here (spec: "files small enough to read in
 * one go").
 *
 * **Code lines, not total lines**: comments and blanks are free. Docblocks here carry the "why" a reader would
 * otherwise undo, and a budget that charged for them would punish the habit the project wants.
 *
 * Measured 2026-10-17 the whole of `app/` is small — the largest unit was `ApplyRegistryImportOperation` at 174 of its
 * 180 (batch 07 made it `ApplyRegistryImportFeature`) — so today the budgets bite on one file. They are outlier
 * detectors, not targets: aegis's own note is that they were "chosen to pass today and bite on extremes", and a
 * failure is a prompt to look, not a verdict.
 *
 * Not ported: aegis measures through an app Job (`MeasureClassLengthsJob`) because its `make check-class-length`
 * ranks units for people. Nothing here needs the ranking, so the count lives in the test helpers.
 */

/** @return array<string, int> code-line budget per layer — the single definition */
function layerLimits(): array
{
    return [
        'Job' => 120,
        'Feature' => 200,
        'Operation' => 180,
        'DTO' => 150,
        'Enum' => 200,
        'Model' => 150,
        'Command' => 300,
        'Other' => 250,
    ];
}

/** Which layer a path (relative to `app/`) belongs to. `/Jobs/` first: path beats suffix. */
function unitLayer(string $path): string
{
    return match (true) {
        str_contains($path, '/Jobs/') => 'Job',
        str_ends_with($path, 'Feature.php') => 'Feature',
        str_ends_with($path, 'Operation.php') => 'Operation',
        str_starts_with($path, 'Data/Models/') => 'Model',
        str_contains($path, '/DataObjects/'), str_contains($path, '/Data/'), str_starts_with($path, 'Foundation/Inputs/') => 'DTO',
        str_starts_with($path, 'Enums/') => 'Enum',
        str_starts_with($path, 'Console/') => 'Command',
        default => 'Other',
    };
}

/** @return list<string> paths relative to `app/` over their layer's budget, with the count */
function unitsOverBudget(): array
{
    $over = [];

    foreach (phpFiles() as $file) {
        $path = relativePath($file);

        if (codeLineCount($file) > layerLimits()[unitLayer($path)]) {
            $over[] = $path;
        }
    }

    return $over;
}

it('measures the units it is supposed to be guarding', function (): void {
    // The guard on the guard: a counter that returned 0 would pass everything.
    expect(codeLineCount(base_path('app/Services/Console/Features/Registry/ApplyRegistryImportFeature.php')))->toBeGreaterThan(100);
});

it('keeps every unit within its layer budget', function (): void {
    expectOnlyKnownGaps(
        unitsOverBudget(),
        KnownGaps::UNITS_OVER_LAYER_LINE_LIMIT,
        'Over its layer\'s code-line budget (layerLimits()). Describe the unit in one sentence; if it needs an "and", split it',
    );
});

it('files representative paths in the right layer', function (string $path, string $layer): void {
    expect(unitLayer($path))->toBe($layer);
})->with([
    ['Domains/Family/Jobs/GetFamiliesJob.php', 'Job'],
    // A Job's name ending in Operation is still a Job — path beats suffix.
    ['Domains/Family/Jobs/BuildOperation.php', 'Job'],
    ['Services/Church/Features/Family/ShowFamiliesFeature.php', 'Feature'],
    ['Operations/Registration/RegisterFamilyOperation.php', 'Operation'],
    ['Domains/Family/DataObjects/FamilyPortalData.php', 'DTO'],
    ['Services/Church/Data/AddStaffInput.php', 'DTO'],
    ['Foundation/Inputs/ChildInput.php', 'DTO'],
    // Models live under Data/ and must not be swept up as DTOs.
    ['Data/Models/Family.php', 'Model'],
    ['Enums/GenderEnum.php', 'Enum'],
    ['Console/Commands/RegistryApplyCommand.php', 'Command'],
    ['Services/Church/Http/Middleware/RequireAssignment.php', 'Other'],
]);
