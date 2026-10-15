<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia;

/**
 * Typed accessors for Inertia props inside a test.
 *
 * `$page->toArray()['props']['kids']` is `mixed`, so passing it to `collect()` fails PHPStan with
 * "unable to resolve the template type TKey" — an error that says nothing about the actual problem.
 * These narrow once, here, rather than every assertion casting inline.
 */

/**
 * @return array<array-key, mixed>
 */
function props(AssertableInertia $page): array
{
    /** @var array{props?: array<array-key, mixed>} $rendered */
    $rendered = $page->toArray();

    return $rendered['props'] ?? [];
}

/**
 * One prop as an array.
 *
 * @return array<array-key, mixed>
 */
function propArray(AssertableInertia $page, string $key): array
{
    $value = props($page)[$key] ?? [];

    return is_array($value) ? $value : [];
}

/**
 * One prop as a collection, for `firstWhere` and friends.
 *
 * @return Collection<array-key, mixed>
 */
function propCollection(AssertableInertia $page, string $key): Collection
{
    return Collection::make(propArray($page, $key));
}
