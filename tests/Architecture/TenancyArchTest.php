<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/**
 * Every query on a church's own data names the church (plan §3.1, adapted from palo's `TenancyArchTest`).
 *
 * The costliest bug class in a multi-church app is a read or write that reaches another church's families. A
 * test proves the paths that exist are scoped; only a guard stops the next one being added unscoped. It is a
 * guardrail, not proof — authorization is proven by the Feature tests per access-matrix row.
 *
 * A statement passes when it names `organization_id`, or `family_id`: a family belongs to exactly one church
 * (`families.organization_id` is NOT NULL), so a statement scoped to one family is scoped to its church.
 */

/**
 * Models whose rows belong to one church. MagicLink is not here: a link belongs to a person, not a church.
 *
 * @return list<string>
 */
function tenantModels(): array
{
    return ['Family', 'Guardian', 'Child', 'Note', 'DuplicateCandidate', 'RoleAssignment', 'Branch', 'Group', 'FamilyClaim', 'RegistryImportMapping'];
}

/**
 * Reads that deliberately look across EVERY church, with why. Keyed "File — Model".
 *
 * @return array<string, string>
 */
function crossChurchOnPurpose(): array
{
    return [
        'GetUserIsFamilyOnlyJob.php — Guardian' => 'Is this login used ONLY as a parent, anywhere? Protects staff logins in every church from a family-email correction (plan §4.4).',
        'GetUserIsFamilyOnlyJob.php — RoleAssignment' => 'Same question: a staff role in ANY church means the login is not family-only.',
        'GetLandingOptionsJob.php — Guardian' => 'After sign-in: which churches is this login a parent in? The answer is a list of churches (plan §4.5).',
        'GetLandingOptionsJob.php — RoleAssignment' => 'After sign-in: which churches does this login serve in?',
        'GetUserIsStaffJob.php — RoleAssignment' => 'Backup password (plan D7): does this login serve in ANY church right now? Parents never get one.',
    ];
}

/**
 * Statements (split on `;`, comments stripped) that start a query on a tenant model, keyed "File — Model".
 *
 * @return list<array{key: string, statement: string}>
 */
function tenantStatements(): array
{
    $found = [];
    $files = Finder::create()->files()->in(base_path('app'))->exclude('Tests')->name('*.php');

    foreach ($files as $file) {
        $source = (string) preg_replace(['#/\*.*?\*/#s', '#//[^\n]*#'], '', $file->getContents());

        foreach (explode(';', $source) as $statement) {
            foreach (tenantModels() as $model) {
                if (preg_match('/(?<![A-Za-z\\\\])'.$model.'::query\(\)/', $statement) === 1) {
                    $found[] = ['key' => $file->getFilename().' — '.$model, 'statement' => trim((string) preg_replace('/\s+/', ' ', $statement))];
                }
            }
        }
    }

    return $found;
}

/**
 * A `where` (or whereIn) on the column, or a create/firstOrNew payload that sets it — NOT a mere mention: a
 * `whereColumn('children.family_id', 'families.id')` inside a sub-query names `family_id` and scopes nothing.
 * Proven by removal: deleting GetFamiliesJob's church filter fails this test.
 */
function scopesToAChurch(string $statement): bool
{
    return preg_match("/->where(In)?\\(\\s*'(organization_id|family_id)'|'(organization_id|family_id)'\\s*=>/", $statement) === 1;
}

it('finds the queries it is supposed to be guarding', function (): void {
    // The guard on the guard: an empty list would make the real assertion pass forever.
    expect(count(tenantStatements()))->toBeGreaterThan(10);
});

it('names the church (or one family) in every query on a church\'s data', function (): void {
    $violations = [];

    foreach (tenantStatements() as ['key' => $key, 'statement' => $statement]) {
        if (array_key_exists($key, crossChurchOnPurpose())) {
            continue;
        }
        if (! scopesToAChurch($statement)) {
            $violations[] = $key.' — '.mb_substr($statement, -160);
        }
    }

    expect($violations)->toBe([], "A query that names no church reads or writes another church's data:\n".implode("\n", $violations));
});

it('keeps the cross-church list honest', function (): void {
    $keys = array_column(tenantStatements(), 'key');
    $stale = array_values(array_diff(array_keys(crossChurchOnPurpose()), $keys));

    expect($stale)->toBe([], 'Listed as cross-church on purpose but no longer there — remove: '.implode(', ', $stale));
});
