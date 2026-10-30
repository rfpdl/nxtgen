<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Finder\Finder;

use function Tests\Architecture\modelEntryPattern;
use function Tests\Architecture\stripComments;

/**
 * Every query on a church's own data names the church (plan §3.1, adapted from palo's `TenancyArchTest`).
 *
 * The costliest bug class in a multi-church app is a read or write that reaches another church's families. A
 * test proves the paths that exist are scoped; only a guard stops the next one being added unscoped. It is a
 * guardrail, not proof — authorization is proven by the Feature tests per access-matrix row.
 *
 * A statement passes when it names `church_id`, or `family_id`: a family belongs to exactly one church
 * (`families.church_id` is NOT NULL), so a statement scoped to one family is scoped to its church.
 */

/**
 * Models whose rows belong to one church. MagicLink is not here: a link belongs to a person, not a church.
 *
 * @return list<string>
 */
function tenantModels(): array
{
    return ['Family', 'Guardian', 'Child', 'Note', 'DuplicateCandidate', 'RoleAssignment', 'Satellite', 'Squad', 'Schedule', 'Session', 'CheckIn', 'SquadHeadCount', 'CardRequest', 'PrintList', 'FamilyClaim', 'RegistryImportMapping'];
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
        'GetChildWithFamilyJob.php — Child' => 'A photo link carries only the child id: the church is read FROM the child, then the viewer is checked against it (ShowChildPhotoFeature).',
        'GetUserIsStaffJob.php — RoleAssignment' => 'Backup password (plan D7): does this login serve in ANY church right now? Parents never get one.',
        'NameKey.php — DB::table(children)' => 'The name-card keys\' backfill (NameKey::fillIn, run by a migration): every child of every church gets the keys of their OWN names, row by row — nothing is read across to another row.',
        'RedeemMagicLinkJob.php — join(family_claims)' => 'Consuming a claim link: the join re-checks the one claim the link names (family_claims.id = magic_links.family_claim_id), and the link was found by its secret token.',
    ];
}

/**
 * Static helpers on a tenant model that query INSIDE the model, where this guard checks them (via `self::`), so
 * their callers are not checked a second time. Keyed "Model::method", with why the church is safe.
 *
 * @return array<string, string>
 */
function modelHelpersScopedInside(): array
{
    return [
        'Note::system' => 'Writes the note with the church of the record it is about ($about->church_id) — Note.php\'s self::query() is checked below.',
    ];
}

/**
 * The tenant models' TABLES, keyed by model, read from the models so a rename cannot leave this list behind.
 *
 * @return array<string, string>
 */
function tenantTables(): array
{
    $tables = [];

    foreach (tenantModels() as $model) {
        $class = "App\\Data\\Models\\{$model}";
        $instance = new $class;
        assert($instance instanceof Model);
        $tables[$model] = $instance->getTable();
    }

    return $tables;
}

/**
 * The ways a statement can reach a church's rows: each one found, with the label that keys it and the names the
 * statement may use for that table (the table and any alias) when it scopes with a qualified column.
 *
 * **TODO #19 — this guard was blind to everything but `Model::query()`.** Now caught as well:
 *
 * - any static call on a tenant model that is a query — through the SHARED `modelEntryPattern()` (Helpers.php),
 *   the same one the Feature, Operation and Middleware guards use: `Child::with(…)`, `Family::find()`, a scope,
 *   and fully or partly qualified names (`\App\Data\Models\Family::query()`);
 * - `self::` / `static::` queries inside the tenant model's own file (`Note::system()` writes through one);
 * - `DB::table('children')`, a sub-query's `->from('children')`, and every `->join…('guardians', …)` form — in
 *   either quote, with or without an alias (`'children as c'`).
 *
 * **What it still CANNOT see**, honestly, so nobody mistakes it for proof: `joinSub` / `leftJoinSub` / `fromSub`
 * (the table is inside a closure or another builder), `DB::select()` / `DB::statement()` / `selectRaw` / any raw
 * SQL string, a table or model named through a variable (`DB::table($table)`, `$class::query()`), a query built
 * across several statements (it reads one `;`-statement at a time — a builder kept in a variable and scoped on a
 * later line reads as unscoped, which errs the safe way, but one scoped on an EARLIER line and then joined
 * reads as scoped), and relations (`$family->children()` is scoped by its parent, and that is relied on).
 *
 * @return list<array{label: string, names: list<string>}>
 */
function tenantQueriesIn(string $statement, ?string $ownModel = null): array
{
    $found = [];

    preg_match_all(modelEntryPattern(tenantModels()), $statement, $calls, PREG_SET_ORDER);
    $models = [];
    foreach ($calls as $call) {
        if (! array_key_exists("{$call[1]}::{$call[2]}", modelHelpersScopedInside())) {
            $models[$call[1]] = true;
        }
    }
    foreach (array_keys($models) as $model) {
        $found[] = ['label' => $model, 'names' => [tenantTables()[$model]]];
    }

    // Inside the model's own file — but only a QUERY through self/static, not a private helper (`self::like()`).
    if ($ownModel !== null) {
        if (preg_match('/(?<![\w\\\\$>:])(self|static)::(query|where\w*|with|find\w*|first\w*|create\w*|update\w*|insert\w*|upsert|destroy|all)\s*\(/', $statement) === 1) {
            $found[] = ['label' => $ownModel, 'names' => [tenantTables()[$ownModel]]];
        }
    }

    foreach (tenantTables() as $table) {
        $named = "\\(\\s*(['\"]){$table}(?:\\s+as\\s+(\\w+))?\\1";
        $forms = [
            'DB::table' => '/DB::table'.$named.'/i',
            'from' => '/->from'.$named.'/i',
            'join' => '/->(?:join|leftJoin|rightJoin|crossJoin|joinWhere|leftJoinWhere|rightJoinWhere)'.$named.'/i',
        ];

        foreach ($forms as $form => $regex) {
            if (preg_match_all($regex, $statement, $m) > 0) {
                $found[] = ['label' => "{$form}({$table})", 'names' => array_values(array_unique(array_filter([$table, ...$m[2]])))];
            }
        }
    }

    return $found;
}

/**
 * Statements (split on `;`, comments stripped by the tokeniser) that start a query on a church's rows, keyed
 * "File — what".
 *
 * @return list<array{key: string, statement: string, names: list<string>}>
 */
function tenantStatements(): array
{
    $found = [];
    $files = Finder::create()->files()->in(base_path('app'))->exclude('Tests')->name('*.php');

    foreach ($files as $file) {
        $own = str_contains((string) $file->getRealPath(), '/app/Data/Models/') && in_array($file->getBasename('.php'), tenantModels(), true)
            ? $file->getBasename('.php')
            : null;

        foreach (explode(';', stripComments($file->getContents())) as $statement) {
            foreach (tenantQueriesIn($statement, $own) as ['label' => $label, 'names' => $names]) {
                $found[] = [
                    'key' => $file->getFilename().' — '.$label,
                    'statement' => trim((string) preg_replace('/\s+/', ' ', $statement)),
                    'names' => $names,
                ];
            }
        }
    }

    return $found;
}

/**
 * A `where` (or whereIn) on the column, or a create/firstOrNew payload that sets it — NOT a mere mention: a
 * `whereColumn('children.family_id', 'families.id')` inside a sub-query names `family_id` and scopes nothing.
 * Proven by removal: deleting GetFamiliesJob's church filter fails this test.
 *
 * A QUALIFIED column counts only when the qualifier is the tenant table this query reached, or its alias:
 * `Child::query()->where('families.church_id', …)` with no join to `families` scopes nothing, and a
 * join onto `guardians` is scoped by `guardians.church_id`, not by some other table's. An unqualified
 * column is still accepted — it is the query's own table, or a `whereHas` closure's, which is scoped by its
 * relation.
 *
 * @param  list<string>  $names  the table and aliases the statement reached
 */
function scopesToAChurch(string $statement, array $names): bool
{
    $qualifier = '(?:(?:'.implode('|', array_map(fn (string $n): string => preg_quote($n, '/'), $names)).')\.)?';

    return preg_match("/->where(In)?\\(\\s*['\"]{$qualifier}(church_id|family_id)['\"]|['\"](church_id|family_id)['\"]\\s*=>/", $statement) === 1;
}

it('finds the queries it is supposed to be guarding', function (): void {
    // The guard on the guard: an empty list would make the real assertion pass forever.
    expect(count(tenantStatements()))->toBeGreaterThan(10);
});

it('sees every way into a church\'s rows, not only Model::query()', function (string $statement, array $expected): void {
    // The guard on the new half of the guard (TODO #19): each form below reads a church's rows, and a regex
    // that stopped matching one would let it through silently.
    expect(array_column(tenantQueriesIn($statement), 'label'))->toBe($expected);
})->with([
    'a model query' => ["Child::query()->where('family_id', 1)->get()", ['Child']],
    'with(), no query()' => ["Child::with('family')->get()", ['Child']],
    'a static find' => ['Family::find($id)', ['Family']],
    'fully qualified' => ['\App\Data\Models\Family::query()->get()', ['Family']],
    'partly qualified' => ["Models\\Guardian::where('user_id', 1)", ['Guardian']],
    'a raw table' => ["DB::table('children')->get()", ['DB::table(children)']],
    'a raw table, double quotes' => ['DB::table("children")->get()', ['DB::table(children)']],
    'a sub-query' => ["\$q->whereExists(fn (\$d) => \$d->from('guardians'))", ['from(guardians)']],
    'a join, aliased' => ["User::query()->join('role_assignments as r', 'r.user_id', '=', 'users.id')", ['join(role_assignments)']],
    'a non-tenant table' => ["DB::table('users')->join('magic_links', 'a', '=', 'b')", []],
    'a factory is not a query' => ['Child::factory()->create()', []],
]);

it('sees a model querying itself through self::', function (): void {
    expect(array_column(tenantQueriesIn("self::query()->create(['body' => \$b])", 'Note'), 'label'))->toBe(['Note']);
    expect(tenantQueriesIn("self::query()->create(['body' => \$b])"))->toBe([]);
});

it('accepts a qualified scope only on the table the query reached', function (string $statement, bool $scoped): void {
    $names = array_merge(...array_column(tenantQueriesIn($statement), 'names'));

    expect(scopesToAChurch($statement, $names))->toBe($scoped);
})->with([
    'unqualified' => ["Child::query()->where('church_id', 1)", true],
    'its own table' => ["Child::query()->where('children.church_id', 1)", true],
    'a joined alias' => ["User::query()->join('guardians as g', 'g.user_id', '=', 'users.id')->where('g.church_id', 1)", true],
    'another table, not joined' => ["Child::query()->where('families.church_id', 1)", false],
    'a non-tenant table' => ["User::query()->join('guardians', 'guardians.user_id', '=', 'users.id')->where('users.church_id', 1)", false],
    'a mere mention' => ["Child::query()->whereColumn('children.family_id', 'families.id')", false],
]);

it('names the church (or one family) in every query on a church\'s data', function (): void {
    $violations = [];

    foreach (tenantStatements() as ['key' => $key, 'statement' => $statement, 'names' => $names]) {
        if (array_key_exists($key, crossChurchOnPurpose())) {
            continue;
        }
        if (! scopesToAChurch($statement, $names)) {
            $violations[] = $key.' — '.mb_substr($statement, -160);
        }
    }

    expect($violations)->toBe([], "A query that names no church reads or writes another church's data:\n".implode("\n", $violations));
});

it('checks each helper it trusts inside its own model', function (): void {
    // The honesty half of modelHelpersScopedInside(): a trusted helper must still exist and still be a query
    // this guard reads (and so scopes) in its model's file.
    $keys = array_column(tenantStatements(), 'key');

    foreach (array_keys(modelHelpersScopedInside()) as $helper) {
        [$model, $method] = explode('::', $helper);

        expect(method_exists("App\\Data\\Models\\{$model}", $method))->toBeTrue("{$helper} is gone — remove it from the list");
        expect($keys)->toContain("{$model}.php — {$model}");
    }
});

it('keeps the cross-church list honest', function (): void {
    $keys = array_column(tenantStatements(), 'key');
    $stale = array_values(array_diff(array_keys(crossChurchOnPurpose()), $keys));

    expect($stale)->toBe([], 'Listed as cross-church on purpose but no longer there — remove: '.implode(', ', $stale));
});
