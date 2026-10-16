<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionClass;

/**
 * Polymorphic columns hold short aliases (`family`, `child`), never class names — ported from aegis's
 * `MorphMapArchTest` and adapted. nxtgen has two morph relations, `Note::noteable()` and
 * `DuplicateCandidate::subject()/candidate()`, and AppServiceProvider maps them with `Relation::enforceMorphMap()`.
 *
 * **Why aegis's main rule is not copied** ("every model is in the morph map or on an exclusion list"):
 * `enforceMorphMap()` already makes a missing alias LOUD — Laravel throws `ClassMorphViolationException` the
 * moment an unmapped model is written to a morph column, so the Feature tests that write notes and duplicates
 * fail rather than storing an FQCN. Aegis needs the list because it maps everything up front; here the useful
 * guards are the ones enforcement does NOT give: that enforcement stays on, that each alias still points at a
 * model, and that the aliases hard-coded in queries (`where('noteable_type', 'family')`) are real ones — a typo
 * there throws nothing, it just matches no rows.
 */

/**
 * The `*_type` columns of the morph relations the models declare — `morphTo()` with no arguments uses the
 * method's name, `morphTo('subject', 'subject_type', …)` names it.
 *
 * @return list<string>
 */
function morphTypeColumns(): array
{
    $columns = [];

    foreach (phpFiles('Data/Models') as $file) {
        preg_match_all("/function (\\w+)\\(\\)[^{]*\\{\\s*return \\\$this->morphTo\\(\\s*(?:'\\w+'\\s*,\\s*'(\\w+)')?/", codeOf($file), $m, PREG_SET_ORDER);

        foreach ($m as $relation) {
            $columns[] = ($relation[2] ?? '') !== '' ? $relation[2] : $relation[1].'_type';
        }
    }

    return array_values(array_unique($columns));
}

it('finds the morph relations it is supposed to be guarding', function (): void {
    expect(morphTypeColumns())->toContain('noteable_type', 'subject_type');
    expect(Relation::morphMap())->not->toBeEmpty();
});

it('keeps the morph map enforced', function (): void {
    // The rule everything else here leans on: without it an unmapped model silently stores its class name.
    expect(Relation::requiresMorphMap())->toBeTrue();
});

it('points every morph alias at a concrete model', function (): void {
    $broken = [];

    foreach (Relation::morphMap() as $alias => $class) {
        if (! class_exists($class) || ! is_subclass_of($class, Model::class) || (new ReflectionClass($class))->isAbstract()) {
            $broken[] = "{$alias} => {$class}";
        }
    }

    expect($broken)->toBe([], 'Morph aliases that do not resolve to a model: '.implode(', ', $broken));
});

it('uses only real aliases where a query names a morph type', function (): void {
    $map = Relation::morphMap();
    $unknown = [];
    $columns = implode('|', morphTypeColumns());

    foreach (phpFiles() as $file) {
        preg_match_all("/['\"]({$columns})['\"]\\s*(?:,|=>)\\s*['\"](\\w+)['\"]/", codeOf($file), $m, PREG_SET_ORDER);

        foreach ($m as [, $column, $alias]) {
            if (! str_ends_with($alias, '_id') && ! array_key_exists($alias, $map)) {
                $unknown[] = relativePath($file).": {$column} = '{$alias}'";
            }
        }
    }

    expect($unknown)->toBe([], "Not a morph alias — the query matches no rows:\n  ".implode("\n  ", $unknown));
});
